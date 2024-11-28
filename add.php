<?php
// add_data_costmar.php

session_start();

// تعيين وضع التصحيح (تأكد من تعطيله في بيئة الإنتاج)
define('DEBUG_MODE', true);

header('Content-Type: application/json');
require 'config.php';

// تمكين عرض الأخطاء مؤقتًا للتشخيص فقط
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// دالة لتحويل الأرقام العربية إلى أرقام غربية
function convertArabicToEnglishDigits($number) {
    $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];
    return str_replace($arabic, $english, $number);
}

// دالة لتحميل الملفات مع تسمية باستخدام card_number
function uploadFile($fileInputName, $folderPath, $baseName, $card_number, $allowedExtensions, $maxSize) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];
            $originalFileName = basename($_FILES[$fileInputName]['name']);
            $fileSize = $_FILES[$fileInputName]['size'];
            $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

            // التحقق من امتداد الملف
            if (!in_array($fileExtension, $allowedExtensions)) {
                return ['error' => "نوع الملف لـ $fileInputName غير مسموح به. المسموح: " . implode(', ', $allowedExtensions) . "."];
            }

            // التحقق من حجم الملف
            if ($fileSize > $maxSize) {
                return ['error' => "حجم الملف لـ $fileInputName يتجاوز الحد المسموح به (${maxSize} بايت)."];
            }

            // إنشاء اسم الملف باستخدام card_number ونوع الملف
            if ($baseName === 'military_photo' || $baseName === 'civil_photo') {
                $newFileName = $card_number . '_' . $baseName . '.' . $fileExtension;
            } elseif ($baseName === 'document') {
                $newFileName = $card_number . '_document.pdf';
            } else {
                $newFileName = $card_number . '_' . uniqid($baseName . '_', true) . '.' . $fileExtension;
            }

            $dest_path = $folderPath . '/' . $newFileName;

            // نقل الملف إلى المجلد
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                return ['path' => $dest_path];
            } else {
                return ['error' => "فشل في تحميل الملف لـ $fileInputName."];
            }
        } else {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من الحد المسموح به بواسطة إعدادات الخادم.',
                UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من الحد المسموح به بواسطة النموذج.',
                UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط.',
                UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف.',
                UPLOAD_ERR_NO_TMP_DIR => 'مجلد مؤقت مفقود.',
                UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف إلى القرص.',
                UPLOAD_ERR_EXTENSION => 'تم إيقاف رفع الملف بواسطة امتداد PHP.'
            ];
            $errorCode = $_FILES[$fileInputName]['error'];
            $errorMessage = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'خطأ غير معروف.';
            return ['error' => "خطأ في رفع الملف لـ $fileInputName: $errorMessage"];
        }
    } else {
        // لم يتم رفع ملف جديد
        return ['path' => ''];
    }
}

// دالة لتطهير البيانات
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

try {
    // الاتصال بقاعدة البيانات
    $pdo = getPDOConnection();

    // التحقق من وجود $_POST['card_number']
    if (!isset($_POST['card_number']) || empty($_POST['card_number'])) {
        throw new Exception('رقم البطاقة مطلوب.');
    }

    $card_number_raw = $_POST['card_number'];
    $card_number_sanitized = sanitizeInput($card_number_raw);

    // تحويل الأرقام العربية إلى أرقام غربية
    $card_number = convertArabicToEnglishDigits($card_number_sanitized);

     

    // التحقق من وجود المجلد مسبقًا
    $folderPath = UPLOAD_DIR . '/' . $card_number;
    if (is_dir($folderPath)) {
        throw new Exception('هذه الاستمارة مرفوعة بالفعل.');
    }

    // إنشاء المجلد
    if (!mkdir($folderPath, 0755, true)) {
        throw new Exception('فشل في إنشاء مجلد التحميل.');
    }

    // تحميل الملفات
    $military_upload = uploadFile('military_photo', $folderPath, 'military_photo', $card_number, ['jpg', 'jpeg', 'png', 'gif'], MAX_FILE_SIZE);
    $civil_upload = uploadFile('civil_photo', $folderPath, 'civil_photo', $card_number, ['jpg', 'jpeg', 'png', 'gif'], MAX_FILE_SIZE);
    $pdf_upload = uploadFile('pdf_upload', $folderPath, 'document', $card_number, ['pdf'], MAX_FILE_SIZE * 100); // زيادة حجم PDF

    // عرض حالة رفع الملفات أثناء وضع التصحيح
    if (DEBUG_MODE) {
        echo json_encode([
            'status' => 'debug',
            'message' => 'عرض البيانات الواردة وحالة رفع الملفات.',
            'post_data' => $_POST,
            'files_data' => $_FILES,
            'military_upload' => $military_upload,
            'civil_upload' => $civil_upload,
            'pdf_upload' => $pdf_upload
        ]);
        exit; // إيقاف التنفيذ لمراجعة البيانات
    }

    // التحقق من وجود أخطاء في رفع الملفات
    if (isset($military_upload['error'])) {
        throw new Exception($military_upload['error']);
    }
    if (isset($civil_upload['error'])) {
        throw new Exception($civil_upload['error']);
    }
    if (isset($pdf_upload['error'])) {
        throw new Exception($pdf_upload['error']);
    }

    // استخدام الصور المرفوعة أو الصور الحالية إذا لم يتم رفع صور جديدة
    $military_photo = !empty($military_upload['path']) ? substr($military_upload['path'], strlen(__DIR__) + 1) : sanitizeInput($_POST['existing_military_photo']);
    $civil_photo = !empty($civil_upload['path']) ? substr($civil_upload['path'], strlen(__DIR__) + 1) : sanitizeInput($_POST['existing_civil_photo']);
    
    $pdf_path = !empty($pdf_upload['path']) ? substr($pdf_upload['path'], strlen(__DIR__) + 1) : null;

    // جمع كافة البيانات المطلوبة
    $data = [
        ':military_photo' => $military_photo,
        ':civil_photo' => $civil_photo,
        ':pdf_path' => $pdf_path,
        ':qi_card_number' => sanitizeInput($_POST['qi_card_number'] ?? null),
        ':first_name' => sanitizeInput($_POST['first_name'] ?? null),
        ':father_name' => sanitizeInput($_POST['father_name'] ?? null),
        ':grandfather_name' => sanitizeInput($_POST['grandfather_name'] ?? null),
        ':father_grandfather_name' => sanitizeInput($_POST['father_grandfather_name'] ?? null),
        ':last_name' => sanitizeInput($_POST['last_name'] ?? null),
        ':mother_first_name' => sanitizeInput($_POST['mother_first_name'] ?? null),
        ':mother_father_name' => sanitizeInput($_POST['mother_father_name'] ?? null),
        ':mother_grandfather_name' => sanitizeInput($_POST['mother_grandfather_name'] ?? null),
        ':mother_last_name' => sanitizeInput($_POST['mother_last_name'] ?? null),
        ':height' => isset($_POST['height']) ? floatval($_POST['height']) : null,
        ':weight' => isset($_POST['weight']) ? floatval($_POST['weight']) : null,
        ':issue_date' => sanitizeInput($_POST['issue_date'] ?? null),
        ':birth_place' => sanitizeInput($_POST['birth_place'] ?? null),
        ':gender' => sanitizeInput($_POST['gender'] ?? null),
        ':blood_type' => sanitizeInput($_POST['blood_type'] ?? null),
        ':card_number' => $card_number,
        ':card_code' => sanitizeInput($_POST['card_code'] ?? null),
        ':volunteer_number' => sanitizeInput($_POST['volunteer_number'] ?? null),
        ':card_type' => sanitizeInput($_POST['card_type'] ?? null),
        ':traditional_card_number' => sanitizeInput($_POST['traditional_card_number'] ?? null),
        ':center_number' => sanitizeInput($_POST['center_number'] ?? null),
        ':center_name' => sanitizeInput($_POST['center_name'] ?? null),
        ':traditional_issue_date' => sanitizeInput($_POST['traditional_issue_date'] ?? null),
        ':electronic_card_number' => sanitizeInput($_POST['electronic_card_number'] ?? null),
        ':barcode_number' => sanitizeInput($_POST['barcode_number'] ?? null),
        ':marital_status' => sanitizeInput($_POST['marital_status'] ?? null),
        ':marital_status_numper' => isset($_POST['marital_status_numper']) ? intval($_POST['marital_status_numper']) : 0,
        ':male_children' => isset($_POST['male_children']) ? intval($_POST['male_children']) : 0,
        ':female_children' => isset($_POST['female_children']) ? intval($_POST['female_children']) : 0,
        ':education' => sanitizeInput($_POST['education'] ?? null),
        ':middle_school_type' => sanitizeInput($_POST['middle_school_type'] ?? null),
        ':middle_school_specialization' => sanitizeInput($_POST['middle_school_specialization'] ?? null),
        ':higher_education_department' => sanitizeInput($_POST['higher_education_department'] ?? null),
        ':higher_education_specialization' => sanitizeInput($_POST['higher_education_specialization'] ?? null),
        ':diploma_specialization' => sanitizeInput($_POST['diploma_specialization'] ?? null),
        ':housing_card_number' => sanitizeInput($_POST['housing_card_number'] ?? null),
        ':issuing_authority_housing' => sanitizeInput($_POST['issuing_authority_housing'] ?? null),
        ':housing_issue_date' => sanitizeInput($_POST['housing_issue_date'] ?? null),
        ':province' => sanitizeInput($_POST['province'] ?? null),
        ':district' => sanitizeInput($_POST['district'] ?? null),
        ':subdistrict' => sanitizeInput($_POST['subdistrict'] ?? null),
        ':area' => sanitizeInput($_POST['area'] ?? null),
        ':block' => sanitizeInput($_POST['block'] ?? null),
        ':alley' => sanitizeInput($_POST['alley'] ?? null),
        ':house' => sanitizeInput($_POST['house'] ?? null),
        ':nearest_landmark' => sanitizeInput($_POST['nearest_landmark'] ?? null),
        ':phone_1' => sanitizeInput($_POST['phone_1'] ?? null),
        ':phone_2' => sanitizeInput($_POST['phone_2'] ?? null),
        ':current_address' => sanitizeInput($_POST['current_address'] ?? null),

        // بيانات الزوجات والأطفال (حتى 4 زوجات وكل زوجة حتى 4 أطفال)
        // الزوجة الأولى
        ':wife1_name' => sanitizeInput($_POST['wife_one_name'] ?? null),
        ':wife1_father' => sanitizeInput($_POST['wife_one_father'] ?? null),
        ':wife1_grandfather' => sanitizeInput($_POST['wife_one_grandfather'] ?? null),
        ':wife1_surname' => sanitizeInput($_POST['wife_one_surname'] ?? null),
        ':wife1_num_children' => isset($_POST['wife_one_num_children']) ? intval($_POST['wife_one_num_children']) : 0,
        ':wife1_male_children' => isset($_POST['wife_one_male_children']) ? intval($_POST['wife_one_male_children']) : 0,
        ':wife1_female_children' => isset($_POST['wife_one_female_children']) ? intval($_POST['wife_one_female_children']) : 0,
        ':wife1_child1_name' => sanitizeInput($_POST['wife_one_child_one_name'] ?? null),
        ':wife1_child1_gender' => sanitizeInput($_POST['wife_one_child_one_gender'] ?? null),
        ':wife1_child2_name' => sanitizeInput($_POST['wife_one_child_two_name'] ?? null),
        ':wife1_child2_gender' => sanitizeInput($_POST['wife_one_child_two_gender'] ?? null),
        ':wife1_child3_name' => sanitizeInput($_POST['wife_one_child_three_name'] ?? null),
        ':wife1_child3_gender' => sanitizeInput($_POST['wife_one_child_three_gender'] ?? null),
        ':wife1_child4_name' => sanitizeInput($_POST['wife_one_child_four_name'] ?? null),
        ':wife1_child4_gender' => sanitizeInput($_POST['wife_one_child_four_gender'] ?? null),

        // الزوجة الثانية
        ':wife2_name' => sanitizeInput($_POST['wife_two_name'] ?? null),
        ':wife2_father' => sanitizeInput($_POST['wife_two_father'] ?? null),
        ':wife2_grandfather' => sanitizeInput($_POST['wife_two_grandfather'] ?? null),
        ':wife2_surname' => sanitizeInput($_POST['wife_two_surname'] ?? null),
        ':wife2_num_children' => isset($_POST['wife_two_num_children']) ? intval($_POST['wife_two_num_children']) : 0,
        ':wife2_male_children' => isset($_POST['wife_two_male_children']) ? intval($_POST['wife_two_male_children']) : 0,
        ':wife2_female_children' => isset($_POST['wife_two_female_children']) ? intval($_POST['wife_two_female_children']) : 0,
        ':wife2_child1_name' => sanitizeInput($_POST['wife_two_child_one_name'] ?? null),
        ':wife2_child1_gender' => sanitizeInput($_POST['wife_two_child_one_gender'] ?? null),
        ':wife2_child2_name' => sanitizeInput($_POST['wife_two_child_two_name'] ?? null),
        ':wife2_child2_gender' => sanitizeInput($_POST['wife_two_child_two_gender'] ?? null),
        ':wife2_child3_name' => sanitizeInput($_POST['wife_two_child_three_name'] ?? null),
        ':wife2_child3_gender' => sanitizeInput($_POST['wife_two_child_three_gender'] ?? null),
        ':wife2_child4_name' => sanitizeInput($_POST['wife_two_child_four_name'] ?? null),
        ':wife2_child4_gender' => sanitizeInput($_POST['wife_two_child_four_gender'] ?? null),

        // الزوجة الثالثة
        ':wife3_name' => sanitizeInput($_POST['wife_three_name'] ?? null),
        ':wife3_father' => sanitizeInput($_POST['wife_three_father'] ?? null),
        ':wife3_grandfather' => sanitizeInput($_POST['wife_three_grandfather'] ?? null),
        ':wife3_surname' => sanitizeInput($_POST['wife_three_surname'] ?? null),
        ':wife3_num_children' => isset($_POST['wife_three_num_children']) ? intval($_POST['wife_three_num_children']) : 0,
        ':wife3_male_children' => isset($_POST['wife_three_male_children']) ? intval($_POST['wife_three_male_children']) : 0,
        ':wife3_female_children' => isset($_POST['wife_three_female_children']) ? intval($_POST['wife_three_female_children']) : 0,
        ':wife3_child1_name' => sanitizeInput($_POST['wife_three_child_one_name'] ?? null),
        ':wife3_child1_gender' => sanitizeInput($_POST['wife_three_child_one_gender'] ?? null),
        ':wife3_child2_name' => sanitizeInput($_POST['wife_three_child_two_name'] ?? null),
        ':wife3_child2_gender' => sanitizeInput($_POST['wife_three_child_two_gender'] ?? null),
        ':wife3_child3_name' => sanitizeInput($_POST['wife_three_child_three_name'] ?? null),
        ':wife3_child3_gender' => sanitizeInput($_POST['wife_three_child_three_gender'] ?? null),
        ':wife3_child4_name' => sanitizeInput($_POST['wife_three_child_four_name'] ?? null),
        ':wife3_child4_gender' => sanitizeInput($_POST['wife_three_child_four_gender'] ?? null),

        // الزوجة الرابعة
        ':wife4_name' => sanitizeInput($_POST['wife_four_name'] ?? null),
        ':wife4_father' => sanitizeInput($_POST['wife_four_father'] ?? null),
        ':wife4_grandfather' => sanitizeInput($_POST['wife_four_grandfather'] ?? null),
        ':wife4_surname' => sanitizeInput($_POST['wife_four_surname'] ?? null),
        ':wife4_num_children' => isset($_POST['wife_four_num_children']) ? intval($_POST['wife_four_num_children']) : 0,
        ':wife4_male_children' => isset($_POST['wife_four_male_children']) ? intval($_POST['wife_four_male_children']) : 0,
        ':wife4_female_children' => isset($_POST['wife_four_female_children']) ? intval($_POST['wife_four_female_children']) : 0,
        ':wife4_child1_name' => sanitizeInput($_POST['wife_four_child_one_name'] ?? null),
        ':wife4_child1_gender' => sanitizeInput($_POST['wife_four_child_one_gender'] ?? null),
        ':wife4_child2_name' => sanitizeInput($_POST['wife_four_child_two_name'] ?? null),
        ':wife4_child2_gender' => sanitizeInput($_POST['wife_four_child_two_gender'] ?? null),
        ':wife4_child3_name' => sanitizeInput($_POST['wife_four_child_three_name'] ?? null),
        ':wife4_child3_gender' => sanitizeInput($_POST['wife_four_child_three_gender'] ?? null),
        ':wife4_child4_name' => sanitizeInput($_POST['wife_four_child_four_name'] ?? null),
        ':wife4_child4_gender' => sanitizeInput($_POST['wife_four_child_four_gender'] ?? null)
    ];

    // إضافة التحققات الخاصة بالبيانات
    if (!preg_match('/^633015\d{10}$/', $data[':qi_card_number'])) {
        throw new Exception('رقم البطاقة غير صالح. يجب أن يبدأ بـ 633015 ويتكون من 16 رقمًا.');
    }

    if (!preg_match('/^\d{8}$/', $data[':volunteer_number'])) {
        throw new Exception('رقم البطاقة الانتخابية يجب أن يحتوي على 8 أرقام فقط.');
    }

    // بدء معاملة لضمان تكامل البيانات
    $pdo->beginTransaction();

    // إعداد استعلام الإدراج في جدول المقاتلين باستخدام Prepared Statements
    $fighter_sql = "INSERT INTO fighters (
        military_photo, civil_photo, pdf_path, qi_card_number, first_name, father_name, grandfather_name, father_grandfather_name, last_name,
        mother_first_name, mother_father_name, mother_grandfather_name, mother_last_name, height, weight, issue_date,
        birth_place, gender, blood_type, card_number, card_code, volunteer_number, card_type, traditional_card_number,
        center_number, center_name, traditional_issue_date, electronic_card_number, barcode_number, marital_status,
        marital_status_numper, male_children, female_children, education, middle_school_type,
        middle_school_specialization, higher_education_department, higher_education_specialization,
        diploma_specialization, housing_card_number, issuing_authority_housing, housing_issue_date, province,
        district, subdistrict, area, block, alley, house, nearest_landmark, phone_1, phone_2, current_address,
        wife1_name, wife1_father, wife1_grandfather, wife1_surname, wife1_num_children, wife1_male_children, wife1_female_children,
        wife1_child1_name, wife1_child1_gender, wife1_child2_name, wife1_child2_gender, wife1_child3_name, wife1_child3_gender, wife1_child4_name, wife1_child4_gender,
        wife2_name, wife2_father, wife2_grandfather, wife2_surname, wife2_num_children, wife2_male_children, wife2_female_children,
        wife2_child1_name, wife2_child1_gender, wife2_child2_name, wife2_child2_gender, wife2_child3_name, wife2_child3_gender, wife2_child4_name, wife2_child4_gender,
        wife3_name, wife3_father, wife3_grandfather, wife3_surname, wife3_num_children, wife3_male_children, wife3_female_children,
        wife3_child1_name, wife3_child1_gender, wife3_child2_name, wife3_child2_gender, wife3_child3_name, wife3_child3_gender, wife3_child4_name, wife3_child4_gender,
        wife4_name, wife4_father, wife4_grandfather, wife4_surname, wife4_num_children, wife4_male_children, wife4_female_children,
        wife4_child1_name, wife4_child1_gender, wife4_child2_name, wife4_child2_gender, wife4_child3_name, wife4_child3_gender, wife4_child4_name, wife4_child4_gender
    ) VALUES (
        :military_photo, :civil_photo, :pdf_path, :qi_card_number, :first_name, :father_name, :grandfather_name, :father_grandfather_name, :last_name,
        :mother_first_name, :mother_father_name, :mother_grandfather_name, :mother_last_name, :height, :weight, :issue_date,
        :birth_place, :gender, :blood_type, :card_number, :card_code, :volunteer_number, :card_type, :traditional_card_number,
        :center_number, :center_name, :traditional_issue_date, :electronic_card_number, :barcode_number, :marital_status,
        :marital_status_numper, :male_children, :female_children, :education, :middle_school_type,
        :middle_school_specialization, :higher_education_department, :higher_education_specialization,
        :diploma_specialization, :housing_card_number, :issuing_authority_housing, :housing_issue_date, :province,
        :district, :subdistrict, :area, :block, :alley, :house, :nearest_landmark, :phone_1, :phone_2, :current_address,
        :wife1_name, :wife1_father, :wife1_grandfather, :wife1_surname, :wife1_num_children, :wife1_male_children, :wife1_female_children,
        :wife1_child1_name, :wife1_child1_gender, :wife1_child2_name, :wife1_child2_gender, :wife1_child3_name, :wife1_child3_gender, :wife1_child4_name, :wife1_child4_gender,
        :wife2_name, :wife2_father, :wife2_grandfather, :wife2_surname, :wife2_num_children, :wife2_male_children, :wife2_female_children,
        :wife2_child1_name, :wife2_child1_gender, :wife2_child2_name, :wife2_child2_gender, :wife2_child3_name, :wife2_child3_gender, :wife2_child4_name, :wife2_child4_gender,
        :wife3_name, :wife3_father, :wife3_grandfather, :wife3_surname, :wife3_num_children, :wife3_male_children, :wife3_female_children,
        :wife3_child1_name, :wife3_child1_gender, :wife3_child2_name, :wife3_child2_gender, :wife3_child3_name, :wife3_child3_gender, :wife3_child4_name, :wife3_child4_gender,
        :wife4_name, :wife4_father, :wife4_grandfather, :wife4_surname, :wife4_num_children, :wife4_male_children, :wife4_female_children,
        :wife4_child1_name, :wife4_child1_gender, :wife4_child2_name, :wife4_child2_gender, :wife4_child3_name, :wife4_child3_gender, :wife4_child4_name, :wife4_child4_gender
    )";

    $fighter_stmt = $pdo->prepare($fighter_sql);
    $fighter_stmt->execute($data);

    // إنهاء المعاملة
    $pdo->commit();

    // إعادة رسالة نجاح
    echo json_encode(['status' => 'success', 'message' => 'تم تحديث المعلومات بنجاح!']);
} catch (Exception $e) {
    // في حال حدوث خطأ، التراجع عن المعاملة
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // حذف المجلد إذا تم إنشاؤه قبل حدوث الخطأ
    if (isset($folderPath) && is_dir($folderPath)) {
        // حذف الملفات داخل المجلد
        $files = glob($folderPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        // حذف المجلد
        rmdir($folderPath);
    }

    // تسجيل الخطأ في ملف سجل
    error_log($e->getMessage(), 3, __DIR__ . '/errors.log');

    // إعادة رسالة خطأ مفصلة في وضع التصحيح
    if (DEBUG_MODE) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } else {
        // إعادة رسالة خطأ عامة للمستخدم
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء تحديث المعلومات. يرجى المحاولة مرة أخرى.']);
    }
}
?>
