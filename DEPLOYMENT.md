# PandaPickle Deployment Guide

This guide explains how to deploy PandaPickle to Render.com with persistent MySQL database.

## 📋 Prerequisites

- Git repository (GitHub, GitLab, or Bitbucket)
- Render.com account (free tier available)
- Your application code pushed to the repository

## 🚀 Quick Deploy to Render

### Option 1: Deploy via render.yaml (Recommended)

1. **Push your code to Git:**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin YOUR_REPO_URL
   git push -u origin main
   ```

2. **Connect to Render:**
   - Go to https://render.com
   - Click "New" → "Blueprint"
   - Connect your GitHub/GitLab repository
   - Render will detect `render.yaml` and create services automatically

3. **Done!** Render will:
   - Create MySQL database
   - Build Docker image
   - Deploy your application
   - Run database migrations automatically

### Option 2: Manual Deployment

#### Step 1: Create MySQL Database

1. In Render Dashboard, click "New" → "PostgreSQL" (or use external MySQL)
   - Or use a managed MySQL service (PlanetScale, AWS RDS, etc.)

2. Note down the connection details:
   - Host
   - Database Name
   - Username
   - Password

#### Step 2: Create Web Service

1. Click "New" → "Web Service"
2. Connect your Git repository
3. Configure:
   - **Name:** pandapickle
   - **Environment:** Docker
   - **Dockerfile Path:** ./Dockerfile
   - **Instance Type:** Starter (or Free)

4. **Add Environment Variables:**
   ```
   DB_HOST=your_mysql_host
   DB_NAME=pandapickle
   DB_USER=your_mysql_user
   DB_PASSWORD=your_mysql_password
   APP_ENV=production
   ```

5. Click "Create Web Service"

#### Step 3: Initialize Database

After deployment, run migrations:

1. Go to your Render service
2. Open "Shell" tab
3. Run:
   ```bash
   mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < /var/www/html/database/schema.sql
   mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < /var/www/html/database/migration_add_walkin_customer.sql
   mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < /var/www/html/database/migration_add_walkin_openplay.sql
   mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < /var/www/html/database/migration_matches_use_registrations.sql
   mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < /var/www/html/database/migration_add_match_preference.sql
   ```

## 🗄️ Database Options

### Option A: Render PostgreSQL (Free Tier)

**Note:** Your app uses MySQL. To use Render's free PostgreSQL, you'd need to:
1. Change all database code from MySQL to PostgreSQL
2. Update Dockerfile to use `pgsql` instead of `mysql`

### Option B: External MySQL Service

**Recommended Services:**
- **PlanetScale** (Free tier, managed MySQL)
- **Railway** (Affordable, easy to use)
- **AWS RDS** (Production-ready, scalable)
- **DigitalOcean Managed MySQL** (Good pricing)

### Option C: Use Docker Compose Locally

For development/testing:

```bash
docker-compose up -d
```

Access:
- Application: http://localhost:8080
- MySQL: localhost:3306

## 📁 Data Persistence

### Production (Render):
- Database data is persisted in managed MySQL service
- Uploads directory should use object storage (S3, Cloudinary)

### Docker Compose:
- MySQL data: `mysql_data` volume (persists across restarts)
- Uploads: `./uploads` directory (mounted as volume)

## 🔐 Security Checklist

Before deploying to production:

- [ ] Change default passwords in `render.yaml`
- [ ] Set strong database password
- [ ] Enable HTTPS (Render provides free SSL)
- [ ] Set secure session cookie settings
- [ ] Review and update CORS settings
- [ ] Add rate limiting
- [ ] Enable database backups

## 🔄 Auto-Deploy

Render automatically redeploys when you push to main branch:

```bash
git add .
git commit -m "Update feature"
git push origin main
```

## 📊 Monitoring

In Render Dashboard:
- View logs
- Monitor resource usage
- Set up alerts
- View deployment history

## 🐛 Troubleshooting

### Database Connection Fails

Check environment variables:
```bash
echo $DB_HOST
echo $DB_NAME
echo $DB_USER
```

### Uploads Don't Persist

Solution: Use object storage
- AWS S3
- Cloudinary
- Backblaze B2

Update `config/db.php` to use cloud storage URLs.

### MySQL Migrations Not Running

Manually run migrations via Shell:
```bash
cd /var/www/html/database
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < schema.sql
```

## 💰 Cost Estimate

**Free Tier:**
- Render Web Service (with sleep): $0
- External MySQL (PlanetScale free): $0
- **Total:** $0/month

**Paid (Starter):**
- Render Web Service: $7/month
- Render PostgreSQL: $7/month (needs app conversion)
- Or External MySQL: $5-15/month
- **Total:** ~$12-22/month

## 📞 Support

For issues:
1. Check Render logs
2. Review environment variables
3. Test database connection
4. Check file permissions (uploads directory)

## 🎉 Success!

Your application should now be live at:
```
https://your-app-name.onrender.com
```

---

**Note:** This app uses MySQL. If you specifically need MongoDB, significant code changes are required (PHP → Node.js/Python, SQL → NoSQL queries).
