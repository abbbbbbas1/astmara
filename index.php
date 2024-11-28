
<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
 


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استمارة التطوع</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- خط القاهرة -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Datepicker CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <style>
        body {font-family: 'Cairo', sans-serif; background-color: #f9f9f9;}
        .form-container {margin: 20px auto; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); background-color: #fff; max-width: 1200px;}
        .section-title {text-align: center; font-weight: bold; margin: 30px 0 20px; background-color: #7cd390; padding: 10px; border-radius: 5px;}
        .preview-container {width: 150px; height: 150px; border: 1px solid #ddd; margin-top: 10px; display: flex; justify-content: center; align-items: center; overflow: hidden;}
        .preview-container img {width: 100%; height: 100%; object-fit: cover;}
        .error-message {color: red; margin-top: 5px; display: none;}
        .hidden {display: none !important;}
    </style>
</head>
<body>
    <div class="container form-container">
        <h1 class="text-center mb-4">استمارة تحديث معلومات المقاتل</h1>
        <form id="updateForm" action="add_data_costmar.php" method="POST" enctype="multipart/form-data">
              <!-- CSRF Token -->
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
  
    <div class="section-title">الصورة الشخصية</div>  
    <div class="row">  
        <div class="col-md-6 mb-3 text-center">  
            <label for="military-photo-input" class="form-label d-block mb-2 fw-bold" style="font-size: 18px;">الصورة العسكرية:</label>  
            <div id="military-preview" class="preview-container" onclick="document.getElementById('military-photo-input').click();" style="border: 1px solid #ccc; width: 200px; height: 200px; display: grid; justify-content: center; align-items: center; margin: 0 auto; padding: 10px; box-sizing: border-box;">  
                <?php  
                    $military_photo_path = !empty($row['military_photo']) ? $row['military_photo'] : '';  
                    if (!empty($military_photo_path) && file_exists($military_photo_path)):  
                ?>  
                    <img src="<?php echo htmlspecialchars($military_photo_path); ?>" alt="Military Photo" style="max-width: 100%; max-height: 100%; border: 5px solid #f0f0f0; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);">  
                <?php else: ?>  
                    <span style="font-size: 16px; color: #555;">اختيار الصورة من هنا</span>  
                <?php endif; ?>  
                <input type="file" id="military-photo-input" name="military_photo" accept="image/*" style="display: none;" onchange="previewImage(this, 'military-preview', 200)" >  
                <input type="hidden" name="existing_military_photo" value="<?php echo htmlspecialchars($military_photo_path); ?>">  
            </div>  
        </div>  
        <div class="col-md-6 mb-3 text-center">  
            <label for="civil-photo-input" class="form-label d-block mb-2 fw-bold" style="font-size: 18px;">الصورة المدنية:</label>  
            <div id="civil-preview" class="preview-container" onclick="document.getElementById('civil-photo-input').click();" style="border: 1px solid #ccc; width: 200px; height: 200px; display: grid; justify-content: center; align-items: center; margin: 0 auto; padding: 10px; box-sizing: border-box;">  
                <?php  
                    $civil_photo_path = !empty($row['civil_photo']) ? $row['civil_photo'] : '';  
                    if (!empty($civil_photo_path) && file_exists($civil_photo_path)):  
                ?>  
                    <img src="<?php echo htmlspecialchars($civil_photo_path); ?>" alt="Civil Photo" style="max-width: 100%; max-height: 100%; border: 5px solid #f0f0f0; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);">  
                <?php else: ?>  
                    <span style="font-size: 16px; color: #555;">اختيار الصورة من هنا</span>  
                <?php endif; ?>  
                <input type="file" id="civil-photo-input" name="civil_photo" accept="image/*" style="display: none;" onchange="previewImage(this, 'civil-preview', 200)" >  
                <input type="hidden" name="existing_civil_photo" value="<?php echo htmlspecialchars($civil_photo_path); ?>">  
            </div>  
        </div>  
    </div>

             <!-- معلومات بطاقة كي كارد -->
             <div class="section-title">الانتساب (التشكيل)</div>
            <div class="row">
                <div class="col-md-6 mb-3">
                <select id="birth-place" name="antsab" class="form-select" >
                        <option value="" disabled selected>اختيار</option>
                        <option value="amleat">قيادة عمليات</option>
                        <option value="loaa">لواء 13</option>
                       
                    </select>                    
                    <small id="qi-card-error" class="error-message">رقم البطاقة يجب أن يبدأ بـ 633015 ويكون طوله 16 رقمًا، ولا يحتوي على أحرف.</small>
                </div>
            </div>
            <!-- معلومات بطاقة كي كارد -->
            <div class="section-title">معلومات بطاقة كي كارد</div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="text" id="qi-card-number" name="qi_card_number" class="form-control" placeholder="رقم البطاقة" maxlength="16" oninput="validateQiCardNumber()" >
                    <small id="qi-card-error" class="error-message">رقم البطاقة يجب أن يبدأ بـ 633015 ويكون طوله 16 رقمًا، ولا يحتوي على أحرف.</small>
                </div>
            </div>
            
            <!-- المعلوميات الشخصية -->
            <div class="section-title">المعلومات الشخصية</div>
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label for="first-name" class="visually-hidden">الاسم</label>
                    <input type="text" id="first-name" name="first_name" class="form-control" placeholder="الاسم" >
                </div>
                <div class="col-md-2 mb-3">
                    <label for="father-name" class="visually-hidden">اسم الأب</label>
                    <input type="text" id="father-name" name="father_name" class="form-control" placeholder="اسم الأب" >
                </div>
                <div class="col-md-2 mb-3">
                    <label for="grandfather-name" class="visually-hidden">اسم الجد</label>
                    <input type="text" id="grandfather-name" name="grandfather_name" class="form-control" placeholder="اسم الجد" >
                </div>
                <div class="col-md-3 mb-3">
                    <label for="father-grandfather-name" class="visually-hidden">اسم أب الجد</label>
                    <input type="text" id="father-grandfather-name" name="father_grandfather_name" class="form-control" placeholder="اسم أب الجد" >
                </div>
                <div class="col-md-3 mb-3">
                    <label for="last-name" class="visually-hidden">اللقب</label>
                    <input type="text" id="last-name" name="last_name" class="form-control" placeholder="اللقب" >
                </div>
                <!-- تغيير المعرفات والاسماء لتكون فريدة -->
                <div class="col-md-2 mb-3">
                    <label for="mother-first-name" class="visually-hidden">اسم الأم</label>
                    <input type="text" id="mother-first-name" name="mother_first_name" class="form-control" placeholder="اسم الام" >
                </div>
                <div class="col-md-2 mb-3">
                    <label for="mother-father-name" class="visually-hidden">اب الام</label>
                    <input type="text" id="mother-father-name" name="mother_father_name" class="form-control" placeholder=" اب الام" >
                </div>
                <div class="col-md-2 mb-3">
                    <label for="mother-grandfather-name" class="visually-hidden">جد الام</label>
                    <input type="text" id="mother-grandfather-name" name="mother_grandfather_name" class="form-control" placeholder="جد الام" >
                </div>
                <div class="col-md-3 mb-3">
                    <label for="mother-last-name" class="visually-hidden">لقب الام</label>
                    <input type="text" id="mother-last-name" name="mother_last_name" class="form-control" placeholder="لقب الام" >
                </div>
                <div class="col-md-2 mb-3">
                    <label for="height" class="visually-hidden">الطول</label>
                    <input type="number" id="height" name="height" class="form-control" placeholder="الطول" >
                </div>
                <div class="col-md-2 mb-3">
                    <label for="weight" class="visually-hidden">الوزن</label>
                    <input type="number" id="weight" name="weight" class="form-control" placeholder="الوزن" >
                </div>
            </div>
            
            <!-- تاريخ التولد، محل التولد، الجنس، فصيلة الدم -->
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label for="issue-date" class="form-label">تاريخ التولد</label>
                    <input type="date" id="issue-date" name="issue_date" class="form-control" lang="ar" placeholder="تاريخ الإصدار" >
                </div>
                <div class="col-md-4 mb-3">
                    <label for="birth-place" class="form-label">محل التولد</label>
                    <select id="birth-place" name="birth_place" class="form-select">
                        <option value="" disabled selected>اختر المحافظة</option>
                        <option value="Erbil">أربيل</option>
                        <option value="Anbar">الأنبار</option>
                        <option value="Babel">بابل</option>
                        <option value="Baghdad">بغداد</option>
                        <option value="Basra">البصرة</option>
                        <option value="Halabja">حلبجة</option>
                        <option value="Duhok">دهوك</option>
                        <option value="Diwaniya">القادسية</option>
                        <option value="Diyala">ديالى</option>
                        <option value="DhiQar">ذي قار</option>
                        <option value="Sulaymaniyah">السليمانية</option>
                        <option value="Salahaddin">صلاح الدين</option>
                        <option value="Kirkuk">كركوك</option>
                        <option value="Karbala">كربلاء المقدسة</option>
                        <option value="Muthanna">المثنى</option>
                        <option value="Misan">ميسان</option>
                        <option value="Najaf">النجف الأشرف</option>
                        <option value="Nineveh">نينوى</option>
                        <option value="Wasit">واسط</option>
                </select>

                </div>
                <div class="col-md-2 mb-3">
                    <label for="gender" class="form-label">الجنس</label>
                    <select id="gender" name="gender" class="form-select" >
                        <option value="" disabled selected>اختر الجنس</option>
                        <option value="Male">ذكر</option>
                        <option value="Female">أنثى</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="blood-type" class="form-label">فصيلة الدم</label>
                    <select id="blood-type" name="blood_type" class="form-select" >
                        <option value="" disabled selected>اختر فصيلة الدم</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>
            </div>
            
            <!-- معلومات الهوية الوطنية -->
            <div class="section-title">معلومات الهوية الوطنية</div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <input type="text" id="card-number"   name="card_number" class="form-control" placeholder="رقم البطاقة"  maxlength="12" oninput="validateCardNumber(this)">
                    <small class="form-text text-muted">يجب إدخال 12 أرقام فقط.</small>
                </div>
                <div class="col-md-3 mb-3">
                    <input type="text" id="card-code" name="card_code" class="form-control" placeholder="رمز البطاقة" maxlength="9"  oninput="validateCardCode(this)">
                    <small class="form-text text-muted">يجب إدخال أحرف إنجليزية كبيرة وأرقام فقط، وبحد أقصى 9 خانات.</small>
                </div>
            </div>
            <script>
                function validateCardCode(input) {
                    input.value = input.value.replace(/[^A-Z0-9]/g, '');
                }
            </script>
            
            <!-- البطاقة الانتخابية -->
            <div class="section-title">البطاقة الانتخابية</div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="text" id="volunteer-number" name="volunteer_number" class="form-control" placeholder="البطاقة الانتخابية" maxlength="8" oninput="validateVolunteerNumber(this)">
                    <small class="form-text text-muted">يرجى إدخال 8 أرقام فقط.</small>
                </div>
            </div>
            <script>
                function validateVolunteerNumber(input) {
                    input.value = input.value.replace(/[^0-9]/g, '');
                    if (input.value.length > 8) {
                        input.value = input.value.slice(0, 8);
                    }
                }
            </script>
            
            <!-- معلومات البطاقة التموينية -->
            <div class="section-title">معلومات البطاقة التموينية</div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label for="card-type" class="form-label">نوع البطاقة</label>
                    <select id="card-type" name="card_type" class="form-select" onchange="toggleCardTypeFields()" >
                        <option value="" disabled selected>اختر نوع البطاقة</option>
                        <option value="traditional">البطاقة التموينية</option>
                        <option value="electronic">البطاقة التموينية الالكترونية</option>
                    </select>
                </div>
            </div>
            
            <!-- البطاقة التموينية التقليدية -->
            <div id="traditional-card" style="display: none;">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <input type="text" id="traditional-card-number" name="traditional_card_number" class="form-control" placeholder="رقم البطاقة" oninput="convertToEnglishNumbers(this)" maxlength="10">
                        <small class="form-text text-muted">يرجى إدخال أرقام فقط.</small>
                    </div>
                    <script>
                        function convertToEnglishNumbers(input) {
                            input.value = input.value.replace(/[٠١٢٣٤٥٦٧٨٩]/g, function (d) {
                                return String.fromCharCode(d.charCodeAt(0) - 0x0660);
                            });
                            input.value = input.value.replace(/[^0-9]/g, '');
                        }
                    </script>
                    <div class="col-md-3 mb-3">
                        <input type="text" id="center-number" name="center_number" class="form-control" placeholder="رقم المركز">
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="text" id="center-name" name="center_name" class="form-control" placeholder="اسم المركز">
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="date" id="traditional-issue-date" name="traditional_issue_date" class="form-control" lang="ar" placeholder="تاريخ الإصدار">
                        <small class="form-text text-muted">تاريخ اصدار البطاقة</small>
                    </div>
                </div>
            </div>
            
            <script>
                function validateCardNumber(input) {
                    // تحويل الأرقام العربية إلى أرقام إنجليزية إذا تم إدخالها
                    input.value = input.value.replace(/[٠١٢٣٤٥٦٧٨٩]/g, function (d) {
                        return String.fromCharCode(d.charCodeAt(0) - 0x0660);
                    });

                    // السماح بالأرقام فقط
                    input.value = input.value.replace(/[^0-9]/g, ''); // إزالة أي مدخل غير رقمي

                    // التأكد من الطول (حد أقصى 10 أرقام)
                    if (input.value.length > 10) {
                        input.value = input.value.slice(0, 10); // اقتطاع الإضافي
                    }
                }
            </script>
            
            <!-- البطاقة التموينية الالكترونية -->
            <div id="electronic-card" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input type="text" id="electronic-card-number" name="electronic_card_number" class="form-control" placeholder="رقم البطاقة" maxlength="10" oninput="validateCardNumber(this)">
                        <small class="form-text text-muted">يجب إدخال 10 أرقام إنجليزية فقط.</small>
                    </div>
                    <!-- حقل رقم الباركود -->
                    <div class="col-md-6 mb-3">
                        <input type="text" id="barcode-number" name="barcode_number" class="form-control" placeholder="رقم الباركود">
                    </div>
                </div>
            </div>
            
            <script>
                function toggleCardTypeFields() {
                    const cardType = document.getElementById("card-type").value;
                    document.getElementById("traditional-card").style.display = cardType === "traditional" ? "block" : "none";
                    document.getElementById("electronic-card").style.display = cardType === "electronic" ? "block" : "none";
                }
            </script>
                <!-- معلومات الحالة الاجتماعية والتحصيل الدراسي -->
                <div class="section-title">الحالة الاجتماعية</div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <select id="marital-status" name="marital_status" class="form-select" onchange="toggleMaritalFields()" >
                            <option value="" disabled selected>اختر الحالة الزوجية</option>
                            <option value="Married">متزوج</option>
                            <option value="Single">أعزب</option>
                            <option value="Divorced">مطلق</option>
                            <option value="Widowed">أرمل</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 hidden" id="numper-container">
                        <select id="marital-status-numper" name="marital_status_numper" class="form-select" onchange="toggleMaritalFields_numper()">
                            <option value="" disabled selected>اختر عدد الزوجات</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                </div>
    
                <!-- معلومات متزوج -->
                <div id="Married" class="hidden">
                 
    
                    <!-- معلومات الزوجات -->
                     
                    <!-- الزوجة الأولى -->
                    <div class="wife-section hidden" id="wife-section-1">
                        <h5>الزوجة الأولى</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-one-name" name="wife_one_name" class="form-control" placeholder="اسم الزوجة الأولى">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-one-father" name="wife_one_father" class="form-control" placeholder="اسم والد الزوجة الأولى">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-one-grandfather" name="wife_one_grandfather" class="form-control" placeholder="اسم جد الزوجة الأولى">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-one-surname" name="wife_one_surname" class="form-control" placeholder="اللقب الزوجي للزوجة الأولى">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-one-num-children" name="wife_one_num_children" class="form-control" placeholder="عدد أطفال الزوجة الأولى" min="0" onchange="toggleWifeChildrenFields(1)">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-one-male-children" name="wife_one_male_children" class="form-control" placeholder="عدد الذكور للزوجة الأولى" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-one-female-children" name="wife_one_female_children" class="form-control" placeholder="عدد الإناث للزوجة الأولى" min="0">
                            </div>
                        </div>
                        <!-- تفاصيل الأطفال للزوجة الأولى -->
                        <div id="wife-1-children-details" class="hidden">
                            <h6>تفاصيل الأطفال للزوجة الأولى</h6>
                            <div id="wife-1-child-details-container">
                                <!-- Child 1 Details -->
                                <div class="child-details" id="wife-1-child-1">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_one_child_one_name" class="form-control" placeholder="اسم الطفل الأول">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_one_child_one_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 2 Details -->
                                <div class="child-details hidden" id="wife-1-child-2">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_one_child_two_name" class="form-control" placeholder="اسم الطفل الثاني">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_one_child_two_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 3 Details -->
                                <div class="child-details hidden" id="wife-1-child-3">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_one_child_three_name" class="form-control" placeholder="اسم الطفل الثالث">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_one_child_three_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 4 Details -->
                                <div class="child-details hidden" id="wife-1-child-4">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_one_child_four_name" class="form-control" placeholder="اسم الطفل الرابع">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_one_child_four_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
    
                    <!-- الزوجة الثانية -->
                    <div class="wife-section hidden" id="wife-section-2">
                        <h5>الزوجة الثانية</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-two-name" name="wife_two_name" class="form-control" placeholder="اسم الزوجة الثانية">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-two-father" name="wife_two_father" class="form-control" placeholder="اسم والد الزوجة الثانية">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-two-grandfather" name="wife_two_grandfather" class="form-control" placeholder="اسم جد الزوجة الثانية">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-two-surname" name="wife_two_surname" class="form-control" placeholder="اللقب الزوجي للزوجة الثانية">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-two-num-children" name="wife_two_num_children" class="form-control" placeholder="عدد أطفال الزوجة الثانية" min="0" onchange="toggleWifeChildrenFields(2)">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-two-male-children" name="wife_two_male_children" class="form-control" placeholder="عدد الذكور للزوجة الثانية" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-two-female-children" name="wife_two_female_children" class="form-control" placeholder="عدد الإناث للزوجة الثانية" min="0">
                            </div>
                        </div>
                        <!-- تفاصيل الأطفال للزوجة الثانية -->
                        <div id="wife-2-children-details" class="hidden">
                            <h6>تفاصيل الأطفال للزوجة الثانية</h6>
                            <div id="wife-2-child-details-container">
                                <!-- Child 1 Details -->
                                <div class="child-details" id="wife-2-child-1">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_two_child_one_name" class="form-control" placeholder="اسم الطفل الأول">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_two_child_one_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 2 Details -->
                                <div class="child-details hidden" id="wife-2-child-2">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_two_child_two_name" class="form-control" placeholder="اسم الطفل الثاني">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_two_child_two_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 3 Details -->
                                <div class="child-details hidden" id="wife-2-child-3">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_two_child_three_name" class="form-control" placeholder="اسم الطفل الثالث">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_two_child_three_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 4 Details -->
                                <div class="child-details hidden" id="wife-2-child-4">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_two_child_four_name" class="form-control" placeholder="اسم الطفل الرابع">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_two_child_four_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- الزوجة الثالثة -->
                    <div class="wife-section hidden" id="wife-section-3">
                        <h5>الزوجة الثالثة</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-three-name" name="wife_three_name" class="form-control" placeholder="اسم الزوجة الثالثة">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-three-father" name="wife_three_father" class="form-control" placeholder="اسم والد الزوجة الثالثة">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-three-grandfather" name="wife_three_grandfather" class="form-control" placeholder="اسم جد الزوجة الثالثة">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-three-surname" name="wife_three_surname" class="form-control" placeholder="اللقب الزوجي للزوجة الثالثة">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-three-num-children" name="wife_three_num_children" class="form-control" placeholder="عدد أطفال الزوجة الثالثة" min="0" onchange="toggleWifeChildrenFields(3)">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-three-male-children" name="wife_three_male_children" class="form-control" placeholder="عدد الذكور للزوجة الثالثة" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-three-female-children" name="wife_three_female_children" class="form-control" placeholder="عدد الإناث للزوجة الثالثة" min="0">
                            </div>
                        </div>
                        <!-- تفاصيل الأطفال للزوجة الثالثة -->
                        <div id="wife-3-children-details" class="hidden">
                            <h6>تفاصيل الأطفال للزوجة الثالثة</h6>
                            <div id="wife-3-child-details-container">
                                <!-- Child 1 Details -->
                                <div class="child-details" id="wife-3-child-1">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_three_child_one_name" class="form-control" placeholder="اسم الطفل الأول">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_three_child_one_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 2 Details -->
                                <div class="child-details hidden" id="wife-3-child-2">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_three_child_two_name" class="form-control" placeholder="اسم الطفل الثاني">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_three_child_two_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 3 Details -->
                                <div class="child-details hidden" id="wife-3-child-3">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_three_child_three_name" class="form-control" placeholder="اسم الطفل الثالث">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_three_child_three_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 4 Details -->
                                <div class="child-details hidden" id="wife-3-child-4">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_three_child_four_name" class="form-control" placeholder="اسم الطفل الرابع">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_three_child_four_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- الزوجة الرابعة -->
                    <div class="wife-section hidden" id="wife-section-4">
                        <h5>الزوجة الرابعة</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-four-name" name="wife_four_name" class="form-control" placeholder="اسم الزوجة الرابعة">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-four-father" name="wife_four_father" class="form-control" placeholder="اسم والد الزوجة الرابعة">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-four-grandfather" name="wife_four_grandfather" class="form-control" placeholder="اسم جد الزوجة الرابعة">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="text" id="wife-four-surname" name="wife_four_surname" class="form-control" placeholder="اللقب الزوجي للزوجة الرابعة">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-four-num-children" name="wife_four_num_children" class="form-control" placeholder="عدد أطفال الزوجة الرابعة" min="0" onchange="toggleWifeChildrenFields(4)">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-four-male-children" name="wife_four_male_children" class="form-control" placeholder="عدد الذكور للزوجة الرابعة" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="number" id="wife-four-female-children" name="wife_four_female_children" class="form-control" placeholder="عدد الإناث للزوجة الرابعة" min="0">
                            </div>
                        </div>
                        <!-- تفاصيل الأطفال للزوجة الرابعة -->
                        <div id="wife-4-children-details" class="hidden">
                            <h6>تفاصيل الأطفال للزوجة الرابعة</h6>
                            <div id="wife-4-child-details-container">
                                <!-- Child 1 Details -->
                                <div class="child-details" id="wife-4-child-1">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_four_child_one_name" class="form-control" placeholder="اسم الطفل الأول">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_four_child_one_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 2 Details -->
                                <div class="child-details hidden" id="wife-4-child-2">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_four_child_two_name" class="form-control" placeholder="اسم الطفل الثاني">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_four_child_two_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 3 Details -->
                                <div class="child-details hidden" id="wife-4-child-3">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_four_child_three_name" class="form-control" placeholder="اسم الطفل الثالث">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_four_child_three_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child 4 Details -->
                                <div class="child-details hidden" id="wife-4-child-4">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <input type="text" name="wife_four_child_four_name" class="form-control" placeholder="اسم الطفل الرابع">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <select name="wife_four_child_four_gender" class="form-control">
                                                <option value="" disabled selected>اختر الجنس</option>
                                                <option value="Male">ذكر</option>
                                                <option value="Female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- معلومات الحصيل الدراسي -->
                <div class="section-title mt-5">معلومات الحصيل الدراسي</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="education" class="form-label">التحصيل الدراسي</label>
                        <select id="education" name="education" class="form-select" onchange="toggleEducationFields()" >
                            <option value="" disabled selected>اختر التحصيل الدراسي</option>
                            <option value="illiterate">أمي</option>
                            <option value="read_write">يقرأ ويكتب</option>
                            <option value="primary">ابتدائي</option>
                            <option value="middle">متوسط</option>
                            <option value="secondary">اعدادية</option>
                            <option value="diploma">دبلوم</option>
                            <option value="bachelor">بكالوريوس</option>
                            <option value="master">ماجستير</option>
                            <option value="phd">دكتوراه</option>
                            <option value="religious">حوزوي</option>
                        </select>
                    </div>
    
                    <!-- الحقول الإضافية للتحصيل الدراسي المتقدم -->
                    <div id="advanced-education-fields" class="col-md-8 hidden">
                        <!-- لإعدادي: اختيار نوع التحصيل والتخصص الدقيق -->
                        <div id="middle-school-fields" class="hidden mb-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="middle-school-type" class="form-label">نوع التحصيل</label>
                                    <select id="middle-school-type" name="middle_school_type" class="form-select" >
                                        <option value="" disabled selected>اختر نوع التحصيل</option>
                                        <option value="science">علمي</option>
                                        <option value="arts">أدبي</option>
                                        <option value="vocational">مهني</option>
                                     </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="middle-school-specialization" class="form-label">التخصص الدقيق</label>
                                    <input type="text" id="middle-school-specialization" name="middle_school_specialization" class="form-control" placeholder="التخصص الدقيق">
                                </div>
                            </div>
                        </div>
    
                        <!-- لإدراج: قسم والتخصص الدقيق -->
                        <div id="higher-education-fields" class="hidden mb-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="higher-education-department" class="form-label">القسم</label>
                                    <input type="text" id="higher-education-department" name="higher_education_department" class="form-control" placeholder="القسم">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="higher-education-specialization" class="form-label">التخصص الدقيق</label>
                                    <input type="text" id="higher-education-specialization" name="higher_education_specialization" class="form-control" placeholder="التخصص الدقيق">
                                </div>
                            </div>
                        </div>
    
                        <!-- لدبلوم: التخصص الدقيق فقط -->
                        <div id="diploma-fields" class="hidden mb-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="diploma-specialization" class="form-label">التخصص الدقيق</label>
                                    <input type="text" id="diploma-specialization" name="diploma_specialization" class="form-control" placeholder="التخصص الدقيق">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- معلومات بطاقة السكن -->
                <div class="section-title">معلومات بطاقة السكن</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <input type="text" id="housing-card-number" name="housing_card_number" class="form-control" placeholder="رقم البطاقة" >
                    </div>
                    <div class="col-md-4 mb-3">
                        <input type="text" id="issuing-authority-housing" name="issuing_authority_housing" class="form-control" placeholder="جهة الإصدار" >
                    </div>
                    <div class="col-md-4 mb-3">
                        <input type="date" id="housing-issue-date" name="housing_issue_date" class="form-control" lang="ar" placeholder="تاريخ الإصدار" >
                        <small class="form-text text-muted">تاريخ اصدار البطاقة</small> 
                    </div>
                </div>
    
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <select id="province" name="province" class="form-select" >
                            <option value="" disabled selected>اختر المحافظة</option>
                            <option value="Erbil">أربيل</option>
                            <option value="Anbar">الأنبار</option>
                            <option value="Babel">بابل</option>
                            <option value="Baghdad">بغداد</option>
                            <option value="Basra">البصرة</option>
                            <option value="Halabja">حلبجة</option>
                            <option value="Duhok">دهوك</option>
                            <option value="Diwaniya">القادسية</option>
                            <option value="Diyala">ديالى</option>
                            <option value="DhiQar">ذي قار</option>
                            <option value="Sulaymaniyah">السليمانية</option>
                            <option value="Salahaddin">صلاح الدين</option>
                            <option value="Kirkuk">كركوك</option>
                            <option value="Karbala">كربلاء المقدسة</option>
                            <option value="Muthanna">المثنى</option>
                            <option value="Misan">ميسان</option>
                            <option value="Najaf">النجف الأشرف</option>
                            <option value="Nineveh">نينوى</option>
                            <option value="Wasit">واسط</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="text" id="district" name="district" class="form-control" placeholder="القضاء" >
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="text" id="subdistrict" name="subdistrict" class="form-control" placeholder="الناحية" >
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="text" id="area" name="area" class="form-control" placeholder="المنطقة" >
                    </div>
                </div>
    
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <input type="text" id="block" name="block" class="form-control" placeholder="المحلة" >
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="text" id="alley" name="alley" class="form-control" placeholder="الزقاق" >
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="text" id="house" name="house" class="form-control" placeholder="الدار" >
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="text" id="nearest-landmark" name="nearest_landmark" class="form-control" placeholder="أقرب نقطة دالة" >
                    </div>
                </div>
    
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <input type="tel" id="phone-1" name="phone_1" class="form-control" placeholder="رقم الهاتف" >
                    </div>
                    <div class="col-md-4 mb-3">
                        <input type="tel" id="phone-2" name="phone_2" class="form-control" placeholder="الرقم البديل">
                    </div>
                   
                    <div class="col-md-4 mb-3">
                        <input type="text" id="current-address" name="current_address" class="form-control" placeholder="عنوان السكن الحالي" >
                    </div>                
                </div>
                <!-- تحميل ملف PDF -->
                <div class="section-title">تحميل ملف PDF</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="pdf-upload" class="form-label fw-bold">تحميل ملف PDF:</label>
                        <input type="file" id="pdf-upload" name="pdf_upload" accept="application/pdf" class="form-control" required>
                        <small class="form-text text-muted">يرجى تحميل ملف PDF فقط.</small>
                    </div>
                </div>
    
                <!-- دالات JavaScript -->
                <script>
                    // دالة لإظهار أو إخفاء حقول التحصيل الدراسي المتقدمة بناءً على اختيار التحصيل الدراسي
                    function toggleEducationFields() {
                        const educationLevel = document.getElementById("education").value;
                        const advancedFields = document.getElementById("advanced-education-fields");
    
                        // إظهار الحقول إذا كان التحصيل الدراسي من الإعدادي، الدبلوم، البكالوريوس، الماجستير، أو الدكتوراه
                        if (["secondary", "diploma", "bachelor", "master", "phd"].includes(educationLevel)) {
                            advancedFields.classList.remove("hidden");
                        } else {
                            advancedFields.classList.add("hidden");
                        }
    
                        // إظهار أو إخفاء الحقول الفرعية بناءً على التحصيل الدراسي المختار
                        document.getElementById("middle-school-fields").classList.add("hidden");
                        document.getElementById("higher-education-fields").classList.add("hidden");
                        document.getElementById("diploma-fields").classList.add("hidden");
    
                        if (educationLevel === "secondary") {
                            document.getElementById("middle-school-fields").classList.remove("hidden");
                        } else if (["bachelor", "master", "phd"].includes(educationLevel)) {
                            document.getElementById("higher-education-fields").classList.remove("hidden");
                        } else if (educationLevel === "diploma") {
                            document.getElementById("diploma-fields").classList.remove("hidden");
                        }
                    }
    
                    function toggleMaritalFields() {
                        const maritalStatus = document.getElementById("marital-status").value;
                        const isMarried = maritalStatus === "Married";
                        document.getElementById("Married").classList.toggle("hidden", !isMarried);
                        document.getElementById("numper-container").classList.toggle("hidden", !isMarried);
    
                        if (!isMarried) {
                            // Reset and hide wife sections if not married
                            resetWifeSections();
                        }
                    }
    
                    function toggleMaritalFields_numper() {
                        const numper = parseInt(document.getElementById("marital-status-numper").value) || 0;
                        for (let i = 1; i <= 4; i++) {
                            const wifeSection = document.getElementById(`wife-section-${i}`);
                            const showSection = i <= numper;
                            wifeSection.classList.toggle("hidden", !showSection);
                        }
                    }
    
                    function toggleWifeChildrenFields(wifeNumber) {
                        const numChildrenInput = document.getElementById(`wife-${wifeNumber}-num-children`);
                        const numChildren = parseInt(numChildrenInput.value) || 0;
                        const childrenDetails = document.getElementById(`wife-${wifeNumber}-children-details`);
                        childrenDetails.classList.toggle("hidden", numChildren === 0);
                        for (let i = 1; i <= 4; i++) {
                            const childDetail = document.getElementById(`wife-${wifeNumber}-child-${i}`);
                            childDetail.classList.toggle("hidden", i > numChildren);
                        }
                    }
    
                    function resetWifeSections() {
                        for (let i = 1; i <= 4; i++) {
                            const wifeSection = document.getElementById(`wife-section-${i}`);
                            wifeSection.classList.add("hidden");
                            resetWifeFields(i);
                        }
                    }
    
                    function resetWifeFields(wifeNumber) {
                        const inputs = document.querySelectorAll(`#wife-section-${wifeNumber} input`);
                        inputs.forEach(input => input.value = "");
                        const selects = document.querySelectorAll(`#wife-section-${wifeNumber} select`);
                        selects.forEach(select => select.selectedIndex = 0);
                        const childrenDetails = document.getElementById(`wife-${wifeNumber}-children-details`);
                        if (childrenDetails) {
                            childrenDetails.classList.add("hidden");
                            for (let i = 1; i <= 4; i++) {
                                const childDetail = document.getElementById(`wife-${wifeNumber}-child-${i}`);
                                childDetail.classList.add("hidden");
                                const childInputs = childDetail.querySelectorAll("input, select");
                                childInputs.forEach(input => {
                                    if (input.tagName.toLowerCase() === 'select') {
                                        input.selectedIndex = 0;
                                    } else {
                                        input.value = "";
                                    }
                                });
                            }
                        }
                    }
                </script>
    
                <!-- الحقول الإضافية للتحصيل الدراسي المتقدم -->
                <!-- (تم تضمينها في الجزء الأول) -->
    
                <!-- دالات إضافية للتحقق من صحة بطاقة كي كارد -->
                <script>
                    function validateQiCardNumber() {
                        const qiCardInput = document.getElementById("qi-card-number");
                        const errorMessage = document.getElementById("qi-card-error");
                        const value = qiCardInput.value;
    
                        // تحويل الأرقام العربية إلى إنجليزية
                        qiCardInput.value = qiCardInput.value.replace(/[٠١٢٣٤٥٦٧٨٩]/g, function (d) {
                            return String.fromCharCode(d.charCodeAt(0) - 0x0660);
                        });
    
                        // التحقق من أن الرقم يبدأ بـ 633015 ويحتوي على 16 رقمًا فقط
                        const regex = /^633015\d{10}$/;
                        if (!regex.test(qiCardInput.value)) {
                            errorMessage.style.display = "block";
                        } else {
                            errorMessage.style.display = "none";
                        }
                    }
                    
                </script>
    
                <!-- دالة معاينة الصور -->
                <script>
                    function previewImage(input, previewId, maxSizeKB) {
                        const preview = document.getElementById(previewId);
                        const file = input.files[0];
                        if (file) {
                            if (file.size > maxSizeKB * 1024) {
                                alert(`الرجاء اختيار صورة أقل من ${maxSizeKB}KB.`);
                                input.value = "";
                                return;
                            }
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                            }
                            reader.readAsDataURL(file);
                        } else {
                            preview.innerHTML = `<span style="font-size: 16px; color: #555;">اختيار الصورة من هنا</span>`;
                        }
                    }
                </script>
                <script>
                // دالة لتحويل الأرقام العربية إلى إنجليزية وإزالة الأحرف غير المسموح بها
                function sanitizeNumberInput(input, maxLength) {
                    // تحويل الأرقام العربية إلى إنجليزية
                    input.value = input.value.replace(/[٠١٢٣٤٥٦٧٨٩]/g, function (d) {
                        return String.fromCharCode(d.charCodeAt(0) - 0x0660);
                    });

                    // السماح بالأرقام فقط
                    input.value = input.value.replace(/[^0-9]/g, '');

                    // التأكد من الطول
                    if (input.value.length > maxLength) {
                        input.value = input.value.slice(0, maxLength);
                    }
                }

                // دالة التحقق من صحة رقم البطاقة الكي كارد
                function validateQiCardNumber() {
                    const qiCardInput = document.getElementById("qi-card-number");
                    const errorMessage = document.getElementById("qi-card-error");
                    const value = qiCardInput.value;

                    // التحقق من أن الرقم يبدأ بـ 633015 ويحتوي على 16 رقمًا فقط
                    const regex = /^633015\d{10}$/;
                    if (!regex.test(value)) {
                        errorMessage.style.display = "block";
                    } else {
                        errorMessage.style.display = "none";
                    }
                }

                // دالة التحقق من صحة رقم البطاقة
                function validateCardNumber(input) {
                    sanitizeNumberInput(input, 12);
                }

                // دالة التحقق من صحة رمز البطاقة
                function validateCardCode(input) {
                    // السماح بالأحرف الإنجليزية الكبيرة والأرقام فقط
                    input.value = input.value.replace(/[^A-Z0-9]/g, '');
                    if (input.value.length > 9) {
                        input.value = input.value.slice(0, 9);
                    }
                }

                // دالة التحقق من رقم البطاقة الانتخابية
                function validateVolunteerNumber(input) {
                    sanitizeNumberInput(input, 8);
                }
            </script>

                <script>
                function validatePDFUpload(input) {
                    const file = input.files[0];
                    if (file) {
                        const allowedTypes = ['application/pdf'];
                        if (!allowedTypes.includes(file.type)) {
                            alert('يرجى تحميل ملف PDF فقط.');
                            input.value = '';
                            return;
                        }
                        const maxSize = 20 * 1024 * 1024; // 20 ميجابايت
                        if (file.size > maxSize) {
                            alert('حجم ملف PDF يتجاوز الحد المسموح به (20 ميجابايت).');
                            input.value = '';
                            return;
                        }
                    }
                }

                document.getElementById('pdf-upload').addEventListener('change', function() {
                    validatePDFUpload(this);
                });
            </script>

                <!-- الحقول الإضافية للتحصيل الدراسي المتقدم -->
                <!-- (تم تضمينها في الجزء الأول) -->
    
                <!-- خاتمة النموذج -->
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-success btn-lg">تحديث المعلومات</button>
                </div>
    
            </form>
        </div>
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
