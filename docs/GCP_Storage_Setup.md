# GCP Cloud Storage Setup Guide

This guide walks you through configuring Google Cloud Storage (GCS) for the SuGanta API project.

## Prerequisites

- A Google Cloud Platform (GCP) account
- PHP 8.2+ with required extensions (already satisfied by the project)

## Step 1: Create a GCP Project (if needed)

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Note your **Project ID** (e.g., `suganta-api-prod`)

## Step 2: Enable Cloud Storage API

1. In GCP Console, go to **APIs & Services** → **Library**
2. Search for **Cloud Storage API**
3. Click **Enable**

## Step 3: Create a Storage Bucket

1. Go to **Cloud Storage** → **Buckets**
2. Click **Create Bucket**
3. Configure:
   - **Name**: e.g., `suganta-api-uploads` (must be globally unique)
   - **Location**: Choose a region close to your users
   - **Storage class**: Standard (recommended for frequent access)
   - **Access control**: 
     - **Uniform** (recommended) → set `GCP_UNIFORM_BUCKET_ACCESS=true` in .env
     - **Fine-grained** → set `GCP_UNIFORM_BUCKET_ACCESS=false`
4. Click **Create**

### Make Bucket Publicly Readable (for direct file URLs)

If you want uploaded files to be publicly accessible via URL:

1. Go to your bucket → **Permissions**
2. Add principal: `allUsers`
3. Role: **Storage Object Viewer**

> **Alternative:** Use signed/temporary URLs for private files. The `HandlesFileStorage` trait uses `Storage::url()` which returns public URLs. For private content, you would need to use `Storage::temporaryUrl()` instead.

## Step 4: Create a Service Account

1. Go to **IAM & Admin** → **Service Accounts**
2. Click **Create Service Account**
3. Name: e.g., `suganta-storage-sa`
4. Click **Create and Continue**

### Grant Permissions

Add these roles:

- **Storage Object Admin** (for full read/write/delete on objects)
- Or **Storage Object Creator** + **Storage Object Viewer** for more restrictive access

5. Click **Continue** → **Done**

### Create JSON Key

1. Click the created service account
2. Go to **Keys** tab → **Add Key** → **Create new key**
3. Choose **JSON** → **Create**
4. Save the downloaded JSON file securely

## Step 5: Store the Key File

**Important:** Never commit the key file to version control.

1. Create a directory: `storage/keys/` (already in `.gitignore`)
2. Move the JSON file: `storage/keys/gcp-service-account.json`
3. Ensure the file is readable by the web server user

```bash
# Linux/macOS - set permissions
chmod 600 storage/keys/gcp-service-account.json
```

## Step 6: Configure .env

Add or update your `.env`:

```env
# Switch upload disk to GCP
FILESYSTEM_UPLOAD_DISK=gcs

# GCP Cloud Storage
GCP_PROJECT_ID=your-project-id
GCP_KEY_FILE=storage/keys/gcp-service-account.json
GCP_BUCKET=your-bucket-name

# Optional: path prefix inside the bucket (e.g., "production/" or "uploads/")
# GCP_PATH_PREFIX=

# Optional: custom domain for file URLs (requires CNAME config)
# GCP_STORAGE_API_URI=

# Set true if your bucket uses Uniform bucket-level access
# Required when you see: "Cannot insert legacy ACL for an object when uniform bucket-level access is enabled"
GCP_UNIFORM_BUCKET_ACCESS=true

# Signed URL expiry in minutes (default 10080 = 7 days). Used for private buckets.
# GCP_SIGNED_URL_EXPIRY_MINUTES=10080
```

### Alternative: Use GOOGLE_APPLICATION_CREDENTIALS

Instead of `GCP_KEY_FILE`, you can set:

```env
GOOGLE_APPLICATION_CREDENTIALS=storage/keys/gcp-service-account.json
```

And ensure `key_file_path` is null in config so the client falls back to this env var. The current config supports both `GCP_KEY_FILE` and `GOOGLE_CLOUD_KEY_FILE`.

## Step 7: Verify Configuration

Clear config cache and test:

```bash
php artisan config:clear
php artisan tinker
```

In Tinker:

```php
$disk = Storage::disk('gcs');
$disk->put('test.txt', 'Hello GCS');
$disk->exists('test.txt');  // should return true
$disk->url('test.txt');     // should return public URL
$disk->delete('test.txt');   // cleanup
```

## File Paths with GCS

When using GCS, the app stores the same path format as local:

```
portfolios/images/portfolio_image_5_xxx.jpg
portfolios/portfolio_file_5_xxx.pdf
support-tickets/support-ticket_ticket_5_xxx.jpg
```

URLs are resolved as:

- **Default**: `https://storage.googleapis.com/your-bucket/portfolios/images/...`
- **With path prefix**: `https://storage.googleapis.com/your-bucket/uploads/portfolios/images/...`
- **Custom domain**: `https://cdn.yourdomain.com/portfolios/images/...` (if `GCP_STORAGE_API_URI` is set)

## Switching Back to Local

To revert to local storage:

```env
FILESYSTEM_UPLOAD_DISK=public
```

No code changes required. All `HandlesFileStorage` operations use the configured disk.

## Troubleshooting

### "Could not find default credentials"

- Ensure `GCP_KEY_FILE` points to a valid JSON file path (absolute or relative to project root)
- Or set `GOOGLE_APPLICATION_CREDENTIALS` in your environment

### "Cannot insert legacy ACL for an object when uniform bucket-level access is enabled"

Set in `.env`:

```env
GCP_UNIFORM_BUCKET_ACCESS=true
```

### 403 Forbidden or 404 Not Found on file URLs

- **Signed URLs (default)**: The app uses signed URLs for GCS, so files work even when the bucket is private. Ensure `GCP_BUCKET` matches your actual bucket name exactly (check the URL – e.g. `suganta-uploads` vs `sugantatutors`).
- **Public bucket option**: To use public URLs instead, add `allUsers` with role **Storage Object Viewer** in the bucket’s Permissions (IAM).

### Key file not found

- Use absolute path: `GCP_KEY_FILE=/var/www/storage/keys/gcp-service-account.json`
- Or path relative to project root: `GCP_KEY_FILE=storage/keys/gcp-service-account.json`

## Security Checklist

- [ ] Service account JSON key is in `storage/keys/` (gitignored)
- [ ] Key file has restrictive permissions (600)
- [ ] Service account has minimum required roles
- [ ] Bucket CORS configured if needed for cross-origin uploads
- [ ] Consider signed URLs for sensitive files instead of public bucket

## Related Documentation

- [Storage Switch Guide](StorageSwitchGuide.md) – Switching between local, S3, and GCS
- [HandlesFileStorage Trait](HandlesFileStorageTrait.md) – How file uploads work in the app
- [Storage Structure](StorageStructure.md) – Directory layout for stored files
