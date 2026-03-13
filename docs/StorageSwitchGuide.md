# Switch Storage to GCP or AWS

**No code changes needed.** Change only the disk configuration.

---

## Quick Switch

### Local (Default)
```env
FILESYSTEM_UPLOAD_DISK=public
```

### AWS S3
```env
FILESYSTEM_UPLOAD_DISK=s3

AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.s3.amazonaws.com
```

### Google Cloud Storage
```env
FILESYSTEM_UPLOAD_DISK=gcs

GCP_PROJECT_ID=your-project-id
GCP_KEY_FILE=path/to/service-account-key.json
GCP_BUCKET=your-bucket-name
```

---

## Setup

### AWS S3

1. **Install package** (if not already):
   ```bash
   composer require league/flysystem-aws-s3-v3 "^3.0"
   ```

2. **Configure .env** with your AWS credentials and bucket

3. **Set `FILESYSTEM_UPLOAD_DISK=s3`**

4. **Bucket permissions**: Ensure public read for URLs, or use signed URLs

### Google Cloud Storage

1. **Package already installed**: `spatie/laravel-google-cloud-storage` (includes `league/flysystem-google-cloud-storage`)

2. **Follow [GCP Storage Setup](GCP_Storage_Setup.md)** for bucket creation and service account setup

3. **Configure .env**:
   ```env
   FILESYSTEM_UPLOAD_DISK=gcs
   GCP_PROJECT_ID=my-project
   GCP_KEY_FILE=storage/keys/gcp-key.json
   GCP_BUCKET=my-bucket
   ```

4. **Bucket permissions**: Set appropriate IAM permissions

---

## What Stays the Same

- ✅ All file paths (e.g., `portfolios/images/portfolio_image_5_xxx.jpg`)
- ✅ HandlesFileStorage trait – no changes
- ✅ Controllers – no changes
- ✅ API responses – URLs auto-generated from configured disk
- ✅ Database – stores same path format

## What You Change

- `.env` – `FILESYSTEM_UPLOAD_DISK` + cloud credentials
- `config/filesystems.php` – disk config (AWS/GCP sections already added)

---

## File Paths

Paths stored in DB stay the same regardless of disk:
```
portfolios/images/portfolio_image_5_20260306120000_abc123_screenshot.jpg
portfolios/portfolio_file_5_20260306120000_def456_document.pdf
support-tickets/support-ticket_ticket_5_xxx_issue.jpg
```

Laravel resolves the correct URL per disk:
- **public**: `http://yoursite.com/storage/portfolios/images/...`
- **s3**: `https://bucket.s3.region.amazonaws.com/portfolios/images/...`
- **gcs**: `https://storage.googleapis.com/bucket/portfolios/images/...`
