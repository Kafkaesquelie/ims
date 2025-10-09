# AdminLTE Inventory Management System

A comprehensive inventory management system built with PHP, MySQL, and AdminLTE, designed for Benguet State University - Bokod Campus.

## 🚀 Features

- Real-time inventory tracking
- Property & equipment management
- Request & approval workflow
- Role-based access control
- Analytics & reporting
- Automated notifications

## 📋 Prerequisites

Before deploying to Render, ensure you have:

- A GitHub account
- A Render account (free tier available)
- Access to a MySQL database (you can use Render's MySQL service or external providers)

## 🏗️ Project Structure

```
AdminLTE/
├── Dockerfile                 # Docker configuration for Render
├── apache-config.conf         # Apache virtual host configuration
├── docker-compose.yml         # Local development setup
├── .dockerignore             # Files to exclude from Docker build
├── .env.example              # Environment variables template
├── init-db.sh                # Database initialization script
├── inv_system (2).sql        # Database schema and data
├── includes/
│   ├── config.php            # Database configuration (updated for env vars)
│   └── ...
├── uploads/                  # File uploads directory
└── ...
```

## 🛠️ Local Development Setup

1. **Clone the repository:**
   ```bash
   git clone <your-repo-url>
   cd AdminLTE
   ```

2. **Start with Docker Compose:**
   ```bash
   docker-compose up -d
   ```

3. **Access the application:**
   - Application: http://localhost:8080
   - MySQL: localhost:3306

4. **Default login credentials:**
   - **Admin:** username: `admin`, password: `admin`
   - **User:** username: `user`, password: `user`
   - **IT:** username: `IT`, password: `user`

## 🚀 Deploying to Render

### Step 1: Prepare Your Repository

1. **Push your code to GitHub:**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git branch -M main
   git remote add origin <your-github-repo-url>
   git push -u origin main
   ```

### Step 2: Set Up MySQL Database

#### Option A: Using Render MySQL (Paid)
1. Go to [Render Dashboard](https://dashboard.render.com/)
2. Click "New +" → "MySQL"
3. Configure:
   - **Name:** `inventory-mysql`
   - **Database:** `inv_system`
   - **User:** `inventory_user`
   - **Region:** Choose closest to your users
4. Click "Create MySQL Instance"
5. Note the connection details provided

#### Option B: Using External MySQL (Free alternatives)
- **PlanetScale** (Free tier available)
- **Aiven** (Free tier available)  
- **Railway** (Free tier available)

### Step 3: Deploy Web Service on Render

1. **Go to Render Dashboard:**
   - Visit https://dashboard.render.com/
   - Click "New +" → "Web Service"

2. **Connect Your Repository:**
   - Choose "Build and deploy from a Git repository"
   - Connect your GitHub account if not already connected
   - Select your AdminLTE repository

3. **Configure the Web Service:**
   - **Name:** `inventory-management-system`
   - **Region:** Choose closest to your users
   - **Branch:** `main`
   - **Root Directory:** Leave empty
   - **Runtime:** `Docker`
   - **Build Command:** Leave empty (Docker handles this)
   - **Start Command:** Leave empty (Docker handles this)

4. **Set Environment Variables:**
   Click "Advanced" and add the following environment variables:

   ```
   DB_HOST=<your-mysql-host>
   DB_USER=<your-mysql-username>
   DB_PASS=<your-mysql-password>
   DB_NAME=inv_system
   ```

   **Example for Render MySQL:**
   ```
   DB_HOST=dpg-xxxxx-a.oregon-postgres.render.com
   DB_USER=inventory_user
   DB_PASS=your-generated-password
   DB_NAME=inv_system
   ```

5. **Deploy:**
   - Click "Create Web Service"
   - Render will automatically build and deploy your application
   - This process takes about 5-10 minutes

### Step 4: Initialize Database

After deployment, you need to import your database:

#### Method 1: Using Render Dashboard (if using Render MySQL)
1. Go to your MySQL service in Render dashboard
2. Click "Connect" and use the provided connection command
3. Import your SQL file:
   ```bash
   mysql -h <host> -u <user> -p<password> <database> < inv_system\ \(2\).sql
   ```

#### Method 2: Using MySQL Client
```bash
# Connect to your database
mysql -h <your-db-host> -u <your-db-user> -p<your-db-password> <your-db-name>

# Then copy and paste the contents of inv_system (2).sql
```

#### Method 3: Using phpMyAdmin or similar tools
Upload the `inv_system (2).sql` file through your database provider's web interface.

### Step 5: Access Your Application

Once deployed, your application will be available at:
```
https://your-service-name.onrender.com
```

## 🔧 Configuration Details

### Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_HOST` | Database host | `dpg-xxxxx.oregon-postgres.render.com` |
| `DB_USER` | Database username | `inventory_user` |
| `DB_PASS` | Database password | `your-secure-password` |
| `DB_NAME` | Database name | `inv_system` |

### Database Configuration

The application automatically uses environment variables for database connection. The `includes/config.php` file has been updated to support both environment variables and local development.

### File Uploads

The `uploads/` directory is created automatically with proper permissions. On Render, uploaded files are stored temporarily and will be lost on service restart. For production, consider:

- Using cloud storage (AWS S3, Cloudinary, etc.)
- Implementing a file upload service

## 🐛 Troubleshooting

### Common Issues and Solutions

1. **Database Connection Failed**
   - Verify environment variables are correctly set
   - Check database server status
   - Ensure database exists and user has proper permissions

2. **Internal Server Error (500)**
   - Check Render logs in the dashboard
   - Verify all PHP extensions are available
   - Check file permissions

3. **Build Failed**
   - Ensure Dockerfile syntax is correct
   - Check if all required files are present
   - Review build logs in Render dashboard

4. **Application Not Loading**
   - Wait for deployment to complete (5-10 minutes)
   - Check service status in Render dashboard
   - Verify port 80 is exposed in Dockerfile

### Viewing Logs

To view logs on Render:
1. Go to your service dashboard
2. Click on "Logs" tab
3. Monitor real-time logs for errors

### Database Connection Testing

Create a simple test file to verify database connectivity:

```php
<?php
// test-db.php
require_once 'includes/config.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    echo "Database connection successful!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
```

## 📊 Performance Optimization

For better performance on Render:

1. **Enable PHP OPcache** (already included in Dockerfile)
2. **Optimize images** in the `uploads/` directory
3. **Use CDN** for static assets
4. **Implement caching** for frequently accessed data

## 🔒 Security Considerations

1. **Change default passwords** immediately after deployment
2. **Use strong database passwords**
3. **Regularly update dependencies**
4. **Enable HTTPS** (automatic on Render)
5. **Set proper file permissions**

## 🆙 Updating the Application

To update your deployed application:

1. Make changes to your local code
2. Commit and push to GitHub:
   ```bash
   git add .
   git commit -m "Update description"
   git push origin main
   ```
3. Render will automatically redeploy (if auto-deploy is enabled)

## 📞 Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review Render's documentation: https://render.com/docs
3. Check application logs in Render dashboard
4. Verify database connectivity

## 📝 Additional Notes

- **Free Tier Limitations:** Render's free tier has some limitations including service sleeping after inactivity
- **Database Backup:** Regularly backup your database
- **Domain Setup:** You can configure custom domains in Render dashboard
- **SSL:** HTTPS is automatically enabled on Render

## 📄 License

This project is developed for Benguet State University - Bokod Campus.

---

**Need Help?** Check the logs in your Render dashboard or refer to the troubleshooting section above.
