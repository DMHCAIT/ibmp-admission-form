## 🎯 **FINAL UPLOAD LIST FOR HOSTING PANEL**

## 📂 **ESSENTIAL FILES TO UPLOAD** (Total: 20 files + 1 folder)

### 🏠 **CORE APPLICATION** (6 files - CRITICAL)
```bash
✅ index.html                    # Main admission form
✅ styles.css                   # Form styling  
✅ enhanced-script.js           # Form JavaScript
✅ config.php                   # Database configuration
✅ submit_application_new.php   # Form submission handler
✅ success.html                 # Success page
```

### 👤 **ADMIN PANEL** (6 files)
```bash
✅ admin_login.php              # Admin login page
✅ admin_logout.php             # Admin logout (small but functional)
✅ view_applications.php        # Applications dashboard
✅ view_application.php         # View single application
✅ edit_application.php         # Edit applications
✅ generate_invoice.php         # Invoice generator
```

### 🔧 **DATABASE TOOLS** (4 files)
```bash
✅ database_setup_complete.php  # Complete database setup (RUN FIRST!)
✅ database_analysis.php        # Database analyzer
✅ fix_database.php            # Fix database issues
✅ check_database.php          # Test connection
```

### 🖼️ **ASSETS** (2 files)
```bash
✅ ibmp logo.png               # Main logo
✅ ibmp-logo.svg              # Vector logo
```

### 🛡️ **SECURITY** (1 file - Optional)
```bash
✅ .htaccess                   # Security settings
```

### 📁 **FOLDERS** (1 folder)
```bash
✅ uploads/                    # File upload directory (create manually)
```

---

## ❌ **DO NOT UPLOAD THESE FILES**

### 🗑️ **Skip These (Development/Documentation)**
```bash
❌ .git/                      # Git repository
❌ *.md files                 # Documentation files
❌ file_*.php                 # Development tools
❌ test_*.php                 # Testing files  
❌ submit_application_simple.php # Duplicate
❌ check_db_api.php           # API test tool
❌ fix_database_columns.php   # Alternative version
```

---

## 🚀 **UPLOAD PROCEDURE**

### **Step 1: Upload Core Files First**
1. Login to Hostinger cPanel
2. Go to File Manager → public_html/
3. Upload these 6 files FIRST:
   - `config.php`
   - `index.html`
   - `styles.css`
   - `enhanced-script.js`
   - `submit_application_new.php`
   - `success.html`

### **Step 2: Create Upload Folder**
- Create folder: `uploads/`
- Set permissions to **755**

### **Step 3: Upload Admin Files**
- Upload all 6 admin_*.php and view_*.php files

### **Step 4: Upload Database Tools**
- Upload all database_*.php files
- Upload check_database.php

### **Step 5: Upload Assets**
- Upload logo files

### **Step 6: Upload Security (Optional)**
- Upload .htaccess

---

## ⚡ **IMMEDIATE TESTING STEPS**

### **1. Database Setup (CRITICAL - Do This First!)**
```
Visit: https://ibmpractitioner.us/database_setup_complete.php
Click: "Run Database Setup"
Wait for: Success confirmation
```

### **2. Test Database Connection**
```
Visit: https://ibmpractitioner.us/check_database.php
Verify: All tests show green ✅
```

### **3. Test Main Form**
```
Visit: https://ibmpractitioner.us/index.html
Fill: Test application
Submit: Verify success page appears
```

### **4. Test Admin Access**
```
Visit: https://ibmpractitioner.us/admin_login.php
Login: admin / IBMP_Admin_2025!
Check: Dashboard loads properly
```

---

## 📊 **FILE SIZE REFERENCE**
- **Total upload size:** ~2-3 MB
- **Core files:** ~500 KB
- **Admin files:** ~800 KB  
- **Database tools:** ~400 KB
- **Assets:** ~200 KB

---

## 🎯 **SUCCESS INDICATORS**

After uploading, you should see:
- ✅ Form loads at: https://ibmpractitioner.us/index.html
- ✅ Submissions work and redirect to success page
- ✅ Admin panel accessible with proper login
- ✅ Database tests all pass
- ✅ Applications appear in admin dashboard

**Ready for production use!** 🚀
