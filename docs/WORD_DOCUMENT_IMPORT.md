# Word Document Import Feature

## Overview
The Learning Center now supports direct import of Word documents (.docx, .doc) which are automatically converted into learning articles.

## Installation

### Local Development (Laragon)
The package is already added to `composer.json`. To install locally:

```bash
# Using Laragon's PHP (if available)
C:\laragon\bin\php\php-8.3\php.exe C:\laragon\bin\composer\composer.phar install

# Or if composer is globally available
composer install
```

### Docker Build
The Dockerfile automatically installs all dependencies including `phpoffice/phpword` when building:

```dockerfile
# Line 49 in Dockerfile
RUN composer install --no-dev --optimize-autoloader --no-interaction
```

**No changes needed** - the dependency is already included in `composer.json` and will be installed during Docker build.

## Required PHP Extensions
PhpOffice/PhpWord requires these extensions (already included in Dockerfile):
- ✅ XML (built-in)
- ✅ ZIP (line 30)
- ✅ GD (line 29)

## Usage

1. Go to **Admin → Learning Center**
2. Click **"Import Word Doc"** button
3. Select:
   - Default category for imported articles
   - Dedicated type (Hunter/Sport/Both)
4. Upload your Word document (.docx or .doc)
5. Click **"Convert & Import Articles"**

The system will:
- Parse the Word document
- Detect headings and create separate articles
- Convert formatting to HTML
- Import all articles automatically

## How It Works

The `WordDocumentConverter` service:
1. Uses PhpOffice/PhpWord to parse the document
2. Detects headings (bold text, larger fonts)
3. Splits content into separate articles
4. Converts formatting to HTML
5. Creates articles with proper structure

## File Size Limits
- Maximum file size: 20MB
- Supported formats: .docx, .doc

## Troubleshooting

### "Class not found" error
- Ensure `composer install` has been run
- Check that `phpoffice/phpword` is in `vendor/` directory

### Import fails
- Check file format (.docx preferred over .doc)
- Ensure document has readable content
- Check file size (max 20MB)

### No articles created
- Document might not have detectable headings
- Try formatting headings as bold text
- System will create one article with all content if no headings found
