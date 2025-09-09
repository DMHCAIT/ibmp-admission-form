# 🚀 IBMP Admission Form - Essential Files for Hosting Panel Upload

## 📋 **CORE FILES TO UPLOAD (REQUIRED)**

### 🏠 **Main Application Files**
```
✅ index.html                    (Main admission form - REQUIRED)
✅ styles.css                   (Form styling - REQUIRED) 
✅ enhanced-script.js           (Form JavaScript - REQUIRED)
✅ config.php                   (Database configuration - REQUIRED)
✅ submit_application_new.php   (Form submission handler - REQUIRED)
✅ success.html                 (Success page after submission - REQUIRED)
```

### 👤 **Admin Panel Files**
```
✅ admin_login.php              (Admin login page)
✅ admin_logout.php             (Admin logout)
✅ view_applications.php        (Main admin dashboard)
✅ view_application.php         (View individual applications)
✅ edit_application.php         (Edit applications)
✅ generate_invoice.php         (Generate invoices/receipts)
```

### 🔧 **Database Management Tools**
```
✅ database_setup_complete.php  (Complete database setup - RUN FIRST!)
✅ database_analysis.php        (Database structure analyzer)
✅ fix_database.php            (Fix missing database fields)
✅ check_database.php          (Test database connection)
```

### 🖼️ **Assets & Media**
```
✅ ibmp logo.png               (Main logo image)
✅ ibmp-logo.svg              (Vector logo for scaling)
✅ uploads/                    (Create this FOLDER for file uploads)
```

### 🛡️ **Security & Configuration**
```
✅ .htaccess                   (Security settings - OPTIONAL but recommended)
```

---

## ❌ **FILES YOU DON'T NEED TO UPLOAD**

### 🗑️ **Development & Testing Files (Skip These)**
```
❌ .git/                      (Git repository folder)
❌ DEPLOYMENT_GUIDE.md        (Documentation file)
❌ PROJECT_STRUCTURE.md       (Documentation file)
❌ file_cleanup.php           (Development tool)
❌ file_management.php        (Development tool)
❌ test_*.php                 (All test files)
❌ check_db_api.php           (API testing tool)
❌ submit_application_simple.php (Duplicate file)
❌ fix_database_columns.php   (Alternative version)
```

---

## 🎯 **UPLOAD ORDER & INSTRUCTIONS**

### **Step 1: Create Folder Structure**
1. Login to your hosting panel (Hostinger/cPanel)
2. Go to File Manager
3. Navigate to `public_html/` or your domain root
4. Create folder: `uploads/` (set permissions to 755)

### **Step 2: Upload Core Files (Upload These First!)**
```
1. config.php                 (Database configuration)
2. index.html                 (Main form)
3. styles.css                 (Styling)
4. enhanced-script.js         (JavaScript)
5. submit_application_new.php (Form handler)
6. success.html               (Success page)
```

### **Step 3: Upload Admin Panel Files**
```
7. admin_login.php
8. admin_logout.php
9. view_applications.php
10. view_application.php
11. edit_application.php
12. generate_invoice.php
```

### **Step 4: Upload Database Tools**
```
13. database_setup_complete.php
14. database_analysis.php
15. fix_database.php
16. check_database.php
```

### **Step 5: Upload Assets**
```
17. ibmp logo.png
18. ibmp-logo.svg
```

### **Step 6: Upload Security File (Optional)**
```
19. .htaccess
```

---

## 🚀 **AFTER UPLOAD - IMMEDIATE STEPS**

### **1. Run Database Setup**
Visit: `https://ibmpractitioner.us/database_setup_complete.php`
- Click "Run Database Setup"
- Wait for success confirmation

### **2. Test Database Connection**
Visit: `https://ibmpractitioner.us/check_database.php`
- Verify all tests pass
- Check for any errors

### **3. Test Main Form**
Visit: `https://ibmpractitioner.us/index.html`
- Fill out a test application
- Verify submission works

### **4. Test Admin Panel**
Visit: `https://ibmpractitioner.us/admin_login.php`
- Username: `admin`
- Password: `IBMP_Admin_2025!`

---

## 📁 **FOLDER PERMISSIONS**
Set these permissions in your hosting panel:
```
uploads/          → 755 or 777
All .php files    → 644
All .html files   → 644
All .css/.js      → 644
All images        → 644
```

---

## 🎯 **ESSENTIAL FILES SUMMARY**

**Total files to upload: 19 files + 1 folder**

**Core System (6 files):**
- index.html, styles.css, enhanced-script.js, config.php, submit_application_new.php, success.html

**Admin Panel (6 files):**
- admin_login.php, admin_logout.php, view_applications.php, view_application.php, edit_application.php, generate_invoice.php

**Database Tools (4 files):**
- database_setup_complete.php, database_analysis.php, fix_database.php, check_database.php

**Assets (2 files):**
- ibmp logo.png, ibmp-logo.svg

**Security (1 file):**
- .htaccess

**Folders (1 folder):**
- uploads/

---

## 🌐 **Your Website URLs After Upload:**

- **Main Form:** https://ibmpractitioner.us/index.html
- **Admin Panel:** https://ibmpractitioner.us/admin_login.php
- **Database Setup:** https://ibmpractitioner.us/database_setup_complete.php
- **Connection Test:** https://ibmpractitioner.us/check_database.php

**Ready to go live!** 🚀
