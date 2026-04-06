# S3 Storage Setup for Recipe Images and PDFs

This guide explains how to configure Amazon S3 (or S3-compatible storage) for storing recipe images and PDFs.

## Overview

Recipe images and PDFs are now configured to use S3 storage by default. This provides:
- Scalable cloud storage
- CDN integration via CloudFront
- Automatic public URLs for images
- Reduced server storage requirements

## Configuration

### 1. Create an S3 Bucket

1. Log into AWS Console
2. Go to S3 service
3. Create a new bucket (e.g., `your-app-recipes`)
4. Configure bucket settings:
   - **Block Public Access**: Disable (to allow public image access)
   - **Bucket Versioning**: Optional
   - **Encryption**: Recommended (AES-256)

### 2. Configure Bucket Permissions

Add a bucket policy to allow public read access for images:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::your-app-recipes/recipes/images/*"
        }
    ]
}
```

### 3. Configure CORS (Optional, for direct browser uploads)

Add CORS configuration to your bucket:

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
        "AllowedOrigins": ["https://yourdomain.com"],
        "ExposeHeaders": ["ETag"]
    }
]
```

### 4. Create IAM User/Role

Create an IAM user with the following policy:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:PutObject",
                "s3:GetObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::your-app-recipes",
                "arn:aws:s3:::your-app-recipes/*"
            ]
        }
    ]
}
```

Save the **Access Key ID** and **Secret Access Key**.

### 5. Configure Environment Variables

Add the following to your `.env` file:

```env
# AWS Configuration
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-app-recipes
AWS_URL=https://your-app-recipes.s3.amazonaws.com

# Optional: Use CloudFront for faster delivery
# AWS_URL=https://d111111abcdef8.cloudfront.net

# Recipe Storage (defaults to 'recipes' disk which uses S3)
RECIPES_IMAGE_DISK=recipes
RECIPES_PDF_DISK=recipes

# Optional: Use separate bucket for recipes
# AWS_RECIPES_BUCKET=your-app-recipes-only
# AWS_RECIPES_URL=https://your-app-recipes-only.s3.amazonaws.com
```

## CloudFront Setup (Optional but Recommended)

CloudFront provides faster image delivery via CDN:

1. Create a CloudFront distribution
2. Set **Origin Domain** to your S3 bucket
3. Configure **Origin Access Control** (OAC) for security
4. Update bucket policy to allow CloudFront access
5. Set `AWS_URL` or `AWS_RECIPES_URL` to your CloudFront URL

Example:
```env
AWS_RECIPES_URL=https://d111111abcdef8.cloudfront.net
```

## Using Different Storage for Development

For local development, you can use local storage:

```env
# Local development
RECIPES_IMAGE_DISK=public
RECIPES_PDF_DISK=local
```

Or use a development S3 bucket:

```env
# Development S3
AWS_RECIPES_BUCKET=your-app-recipes-dev
RECIPES_IMAGE_DISK=recipes
RECIPES_PDF_DISK=recipes
```

## S3-Compatible Storage (DigitalOcean Spaces, Wasabi, etc.)

To use S3-compatible storage like DigitalOcean Spaces:

```env
AWS_ACCESS_KEY_ID=your-spaces-key
AWS_SECRET_ACCESS_KEY=your-spaces-secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=your-space-name
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_URL=https://your-space-name.nyc3.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## File Structure in S3

Files are organized as follows:

```
your-bucket/
├── recipes/
│   ├── images/
│   │   ├── recipe-image-1.jpg
│   │   ├── recipe-image-2.png
│   │   └── ...
│   └── pdfs/
│       ├── recipe-1.pdf
│       ├── recipe-2.pdf
│       └── ...
```

## Verifying Configuration

Test your S3 configuration:

```bash
# Test file upload via tinker
php artisan tinker

Storage::disk('recipes')->put('test.txt', 'Hello S3!');
Storage::disk('recipes')->exists('test.txt');
Storage::disk('recipes')->url('test.txt');
Storage::disk('recipes')->delete('test.txt');
```

## Troubleshooting

### Images not loading in PDFs
- Ensure bucket policy allows public read access
- Verify `AWS_URL` is correct
- Check that images have public visibility

### Upload failing
- Verify IAM credentials have `s3:PutObject` permission
- Check bucket name is correct
- Ensure region matches bucket region

### Access Denied errors
- Verify IAM policy includes all required actions
- Check bucket policy doesn't block access
- Ensure ACLs are disabled or properly configured

## Cost Optimization

- Use CloudFront to reduce S3 data transfer costs
- Set up lifecycle policies to delete old PDFs
- Use S3 Intelligent-Tiering for infrequently accessed files
- Monitor usage with AWS Cost Explorer
