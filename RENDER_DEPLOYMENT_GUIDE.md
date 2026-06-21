# 🚀 Deploy PandaPickle to Render.com - Complete Guide

Your app has been converted to PostgreSQL and is ready for Render!

## ✅ What Was Converted

- ✅ Database schema (MySQL → PostgreSQL)
- ✅ `config/db.php` (mysql → pgsql)
- ✅ `Dockerfile` (mysqli → pgsql extensions)
- ✅ `render.yaml` (configured for PostgreSQL)
- ✅ Security indexes migration (PostgreSQL syntax)
- ✅ Code pushed to GitHub

## 🎯 Step-by-Step Deployment

### **Step 1: Sign Up for Render** (2 minutes)

1. Go to: https://render.com
2. Click **"Get Started for Free"**
3. **Sign up with GitHub** (recommended - easiest)
4. Authorize Render to access your repositories

---

### **Step 2: Deploy Using Blueprint** (5 minutes)

**This is the EASIEST way - Render will set everything up automatically!**

1. **In Render Dashboard**, click **"New +"**
2. Select **"Blueprint"**
3. **Connect your repository**: `naningb16-blip/PandaPickle`
4. Render will detect `render.yaml` automatically
5. Click **"Apply"**

**Render will now automatically create**:
- ✅ PostgreSQL database (free tier)
- ✅ Web service with Docker
- ✅ Environment variables (auto-configured)
- ✅ Connect database to web service

**Wait 5-10 minutes for deployment to complete.**

---

### **Step 3: Monitor Deployment** (5 minutes)

1. **Check "Events" tab** - Shows deployment progress
2. **Check "Logs" tab** - Shows build logs
3. **Wait for**: "Your service is live" message
4. **Copy your URL**: `https://pandapickle-web.onrender.com`

---

### **Step 4: Import Database Schema** (5 minutes)

Once deployed, you need to import your database:

#### **Option A: Using TablePlus** (Recommended)

1. **Get Database Credentials from Render**:
   - Go to your PostgreSQL database in Render dashboard
   - Click **"Connect"**
   - Copy:
     - External Database URL
     - Host
     - Port
     - Database
     - Username
     - Password

2. **Connect TablePlus**:
   ```
   Connection Name: PandaPickle Render
   Database Type:   PostgreSQL
   Host:           [from Render]
   Port:           5432
   User:           [from Render]
   Password:       [from Render]
   Database:       [from Render]
   SSL:            ✅ Enabled
   ```

3. **Import Schema**:
   - Press Ctrl+T (new SQL tab)
   - Open `database/schema.sql`
   - Copy all content
   - Paste in TablePlus
   - Press Ctrl+R (Run)
   - Verify tables created

4. **Import Security Indexes**:
   - Open `database/migration_add_security_indexes.sql`
   - Copy content
   - Paste and run in TablePlus

#### **Option B: Using Render Web Shell**

1. In Render dashboard, go to your web service
2. Click **"Shell"** tab
3. Run these commands:

```bash
# Connect to PostgreSQL
psql $DATABASE_URL

# Copy schema.sql content and paste here
# Then press Enter

# Exit
\q
```

---

### **Step 5: Test Your Deployment** (5 minutes)

1. **Visit your URL**: `https://pandapickle-web.onrender.com`
2. **Test registration**:
   - Go to Register page
   - Create a new account
   - Check if it works ✅

3. **Test login**:
   - Login with your account
   - Should redirect to dashboard ✅

4. **Check database**:
   - Open TablePlus
   - Check `users` table
   - Should see your new user ✅

5. **Test features**:
   - Try creating a reservation
   - Try registering for open play
   - All should work! ✅

---

## 🗄️ TablePlus Setup for Render PostgreSQL

### **Connection Details**:

Get these from Render Dashboard → Your PostgreSQL service → "Connect":

```
┌────────────────────────────────────────────┐
│  Connection: PandaPickle Render            │
│  Type:       PostgreSQL                    │
│                                            │
│  Host:       dpg-XXXXX.oregon-postgres...  │
│  Port:       5432                          │
│  User:       pandapickle_user              │
│  Password:   [from Render]                 │
│  Database:   pandapickle                   │
│                                            │
│  ✅ SSL Mode: Require                      │
│                                            │
│  [ Test ]             [ Connect ]          │
└────────────────────────────────────────────┘
```

### **Import Files in Order**:

1. `database/schema.sql` ← Import first!
2. `database/migration_add_security_indexes.sql` ← Then this

**That's it!** All other migrations are already in schema.sql for PostgreSQL.

---

## 🎯 Quick Commands for TablePlus

After connecting, test with these queries:

```sql
-- Test 1: Show all tables
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'public' 
ORDER BY table_name;
-- Should show: 8 tables

-- Test 2: Check users table
SELECT * FROM users LIMIT 5;

-- Test 3: Check indexes
SELECT indexname, tablename 
FROM pg_indexes 
WHERE schemaname = 'public' 
AND indexname LIKE 'idx%'
ORDER BY tablename, indexname;
-- Should show security indexes

-- Test 4: Insert test court (if needed)
INSERT INTO courts (court_name, status) 
VALUES ('Court 1', 'active'), ('Court 2', 'active')
ON CONFLICT DO NOTHING;
```

---

## 🔧 Troubleshooting

### **Problem: "Application failed to respond"**

**Solution**: Check logs
1. Go to web service in Render
2. Click "Logs" tab
3. Look for error messages
4. Common issue: Database not connected yet (wait a few minutes)

---

### **Problem: "Database connection failed"**

**Solution**: Check environment variables
1. Go to web service → "Environment" tab
2. Verify these exist:
   ```
   DB_HOST      ✓
   DB_PORT      ✓
   DB_NAME      ✓
   DB_USER      ✓
   DB_PASSWORD  ✓
   APP_ENV      ✓
   ```
3. If missing, redeploy using Blueprint

---

### **Problem: "Service unavailable" after 15 minutes**

**Reason**: Free tier sleeps after 15 minutes of inactivity

**Solution**: Upgrade to paid plan ($7/month) OR accept the limitation
- Free tier: First request after sleep takes 30-60 seconds
- Paid tier: Always on, instant response

---

### **Problem: Can't connect TablePlus**

**Solution**: Check SSL settings
- SSL Mode: **Require** (must be enabled)
- Try toggling "SSL" on/off
- Get exact connection string from Render dashboard

---

## 💰 Cost Breakdown

### **Free Tier** (What you get):
- PostgreSQL: **FREE** (1GB storage, 100 hours/month)
- Web Service: **FREE** (sleeps after 15 min inactivity)
- **Total: $0/month**

### **Paid Tier** (For production):
- PostgreSQL: **$7/month** (always on, 10GB storage)
- Web Service: **$7/month** (always on, no sleep)
- **Total: $14/month**

---

## 🎉 Success Checklist

```
[ ] Render account created
[ ] Code pushed to GitHub (main branch)
[ ] Deployed using Blueprint
[ ] PostgreSQL database created
[ ] Web service deployed
[ ] Database schema imported via TablePlus
[ ] Security indexes applied
[ ] Tested registration
[ ] Tested login
[ ] Tested creating reservation
[ ] URL shared with users
```

---

## 📝 Your URLs

**Web Application**:
```
https://pandapickle-web.onrender.com
```

**Admin Panel**:
```
https://pandapickle-web.onrender.com/admin/
```

**Save these links!**

---

## 🔐 Next Steps

1. **Create admin account**:
   - Register normally
   - Use TablePlus to change role to 'admin':
     ```sql
     UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
     ```

2. **Delete test files**:
   - Remove `test_security.php` from repo
   - Commit and push

3. **Add custom domain** (optional):
   - Render allows custom domains on free tier!
   - Go to web service → Settings → Custom Domain

---

## 🎊 You're Live!

Your PandaPickle application is now deployed with:
- ✅ PostgreSQL database
- ✅ Free HTTPS (automatic)
- ✅ Automatic deployments (on git push)
- ✅ All security features intact
- ✅ Production-ready setup

**Congratulations! 🎉**

---

**Questions?** Check Render docs: https://render.com/docs

Last Updated: June 20, 2026
