# Converting Word Document to JSON - Step by Step

## Quick Method: Using ChatGPT

### Step 1: Extract Content from Word Document

1. Open `NRAPA STUDY MATERIAL SPORT.docx` in Microsoft Word
2. Press `Ctrl+A` to select all content
3. Press `Ctrl+C` to copy
4. Go to ChatGPT

### Step 2: Use This Prompt

Copy and paste this entire prompt into ChatGPT, then paste your document content:

```
I need to convert a Word document containing study material into JSON format for importing learning articles into a Laravel application.

The document is about NRAPA Sport Shooter study material. Please convert it into JSON with this exact structure:

{
  "articles": [
    {
      "title": "Article Title Here",
      "category": "Category Name",
      "category_description": "Brief description of the category",
      "excerpt": "1-2 sentence summary of the article",
      "content": "<h2>Main Heading</h2><p>Paragraph content with HTML formatting</p><h3>Subheading</h3><ul><li>Bullet point</li></ul>",
      "is_published": true,
      "is_featured": false,
      "dedicated_type": "sport",
      "published_at": "2026-01-28T12:00:00Z"
    }
  ]
}

Requirements:
1. Split the document into logical articles/sections (each major topic should be a separate article)
2. Use the section headings as article titles
3. Format content with HTML tags:
   - <h2> for main headings
   - <h3> for subheadings
   - <p> for paragraphs
   - <ul><li> for bullet lists
   - <strong> for bold text
   - <em> for italic text
4. Create appropriate categories (e.g., "Safety", "Regulations", "Sport Shooting", "Competition Rules")
5. Generate excerpts from the first paragraph of each section
6. Set dedicated_type to "sport" since this is sport shooter material
7. Set is_published to true
8. Use current date/time for published_at (format: YYYY-MM-DDTHH:MM:SSZ)

Document Content:
[PASTE THE COPIED CONTENT FROM WORD HERE]

Output only valid JSON, ready for import. No explanations or markdown formatting.
```

### Step 3: After ChatGPT Generates JSON

1. Copy the JSON output from ChatGPT
2. Save it as a file: `nrapa-sport-study-material.json`
3. Go to your NRAPA admin panel → Learning Center
4. Click "Import JSON"
5. Upload the JSON file
6. Click "Import Articles"

---

## Alternative: Manual Conversion

If you prefer to convert manually or need more control:

### Step 1: Identify Sections

Look through the document and identify:
- Main topics/sections (these become articles)
- Section headings (these become article titles)
- Related topics (these become categories)

### Step 2: Create JSON Structure

For each section, create an article object:

```json
{
  "title": "Section Title from Document",
  "category": "Sport Shooting",
  "category_description": "Study material for sport shooters",
  "excerpt": "Brief summary of this section",
  "content": "<h2>Heading</h2><p>Content here...</p>",
  "is_published": true,
  "is_featured": false,
  "dedicated_type": "sport",
  "published_at": "2026-01-28T12:00:00Z"
}
```

### Step 3: Format Content

Convert Word formatting to HTML:
- **Bold text** → `<strong>Bold text</strong>`
- *Italic text* → `<em>Italic text</em>`
- Bullet points → `<ul><li>Item 1</li><li>Item 2</li></ul>`
- Numbered lists → `<ol><li>Item 1</li><li>Item 2</li></ol>`
- Paragraphs → `<p>Paragraph text</p>`
- Headings → `<h2>Main Heading</h2>` or `<h3>Subheading</h3>`

---

## Example: Converting a Section

**Original Word Document Section:**
```
Firearm Safety Rules

The following safety rules must always be followed:

1. Always treat every firearm as if it is loaded
2. Never point a firearm at anything you don't intend to destroy
3. Keep your finger off the trigger until ready to shoot
4. Be sure of your target and what is beyond it
```

**Converted JSON Article:**
```json
{
  "title": "Firearm Safety Rules",
  "category": "Safety",
  "category_description": "Essential safety information",
  "excerpt": "The fundamental safety rules that must always be followed when handling firearms.",
  "content": "<h2>Firearm Safety Rules</h2><p>The following safety rules must always be followed:</p><ol><li>Always treat every firearm as if it is loaded</li><li>Never point a firearm at anything you don't intend to destroy</li><li>Keep your finger off the trigger until ready to shoot</li><li>Be sure of your target and what is beyond it</li></ol>",
  "is_published": true,
  "is_featured": false,
  "dedicated_type": "sport",
  "published_at": "2026-01-28T12:00:00Z"
}
```

---

## Tips for Best Results

1. **Large Documents**: If the document is very long, split it into multiple JSON files (10-20 articles per file)

2. **Categories**: Group related articles under the same category:
   - "Safety" - Safety rules and procedures
   - "Regulations" - Legal requirements
   - "Sport Shooting" - Sport-specific content
   - "Competition Rules" - Competition guidelines
   - "Equipment" - Firearm and gear information

3. **Featured Articles**: Mark important/summary articles as `"is_featured": true`

4. **Dedicated Type**: Since this is sport shooter material, use:
   - `"sport"` for sport shooter specific content
   - `"both"` for content relevant to both hunters and sport shooters

5. **Validation**: Before importing, validate your JSON at:
   - https://jsonlint.com/
   - Or use VS Code with JSON validation

---

## Need Help?

If you encounter issues:
1. Check the JSON syntax is valid
2. Ensure all required fields are present
3. Verify HTML tags are properly closed
4. Check that category names are consistent
5. Review the error message in the import modal

For more detailed instructions, see:
- `docs/JSON_IMPORT_GUIDE.md`
- `docs/CHATGPT_PROMPTS.md`
