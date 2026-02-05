# JSON Import Guide - Converting Documents to JSON

This guide provides ChatGPT prompts and instructions for converting documents (Word, PDF, text files) into JSON format for importing into the NRAPA Learning Center and Knowledge Tests.

## Table of Contents
1. [Converting Documents to Learning Articles JSON](#learning-articles)
2. [Converting Documents to Knowledge Test Questions JSON](#knowledge-test-questions)
3. [Tips and Best Practices](#tips)

---

## Learning Articles

### ChatGPT Prompt Template

```
I need to convert a document into JSON format for importing learning articles. Please convert the following content into the required JSON structure.

JSON Format Requirements:
- Root object must have an "articles" array
- Each article must have:
  - "title" (required): Article title
  - "category" (required): Category name (e.g., "Safety", "Regulations", "Training")
  - "category_description" (optional): Brief description of the category
  - "excerpt" (optional): Short summary (1-2 sentences)
  - "content" (required): Full article content (can include HTML tags like <p>, <h2>, <ul>, <li>)
  - "is_published" (optional): true/false (default: false)
  - "is_featured" (optional): true/false (default: false)
  - "dedicated_type" (optional): "hunter", "sport", "both", or null (default: null)
  - "published_at" (optional): ISO 8601 date string (e.g., "2026-01-28T12:00:00Z")

Document Content:
[PASTE YOUR DOCUMENT CONTENT HERE]

Please:
1. Split the document into logical articles (if multiple topics)
2. Create appropriate titles for each article
3. Suggest appropriate categories
4. Generate excerpts from the content
5. Format the content with basic HTML tags (<p>, <h2>, <h3>, <ul>, <li>, <strong>, <em>)
6. Output valid JSON that can be directly imported
```

### Example Output Structure

```json
{
  "articles": [
    {
      "title": "Firearm Safety Fundamentals",
      "category": "Safety",
      "category_description": "Essential safety information for firearm owners",
      "excerpt": "Learn the fundamental safety rules every firearm owner must know and practice.",
      "content": "<h2>Introduction</h2><p>Firearm safety is paramount...</p><h2>Basic Rules</h2><ul><li>Always treat every firearm as if it is loaded</li><li>Never point a firearm at anything you don't intend to destroy</li></ul>",
      "is_published": true,
      "is_featured": false,
      "dedicated_type": "both",
      "published_at": "2026-01-28T12:00:00Z"
    }
  ]
}
```

### Step-by-Step Instructions

1. **Prepare Your Document**
   - Open your Word document, PDF, or text file
   - Copy the content you want to convert
   - If you have multiple articles, you can either:
     - Convert them one at a time, OR
     - Include all content and ask ChatGPT to split it into separate articles

2. **Use the Prompt**
   - Copy the prompt template above
   - Replace `[PASTE YOUR DOCUMENT CONTENT HERE]` with your actual content
   - Paste into ChatGPT

3. **Review and Edit**
   - Check that titles are appropriate
   - Verify categories make sense
   - Ensure HTML formatting is correct
   - Adjust `is_published`, `is_featured`, and `dedicated_type` as needed

4. **Save the JSON**
   - Copy the JSON output from ChatGPT
   - Save it as a `.json` file (e.g., `articles-import.json`)
   - Use the "Import JSON" button in the Learning Center admin page

---

## Knowledge Test Questions

### ChatGPT Prompt Template

```
I need to convert a document containing test questions into JSON format for importing knowledge test questions. Please convert the following content into the required JSON structure.

JSON Format Requirements:
- Root object must have a "questions" array
- Each question must have:
  - "question_type" (required): Either "multiple_choice" or "written"
  - "question_text" (required): The question text
  - "options" (required for multiple_choice): Array of answer options (minimum 2)
  - "correct_answer" (required for multiple_choice): The correct answer (must match one of the options exactly)
  - "points" (optional): Points value (default: 1)
  - "sort_order" (optional): Order number (will be auto-assigned if not provided)
  - "is_active" (optional): true/false (default: true)

Document Content:
[PASTE YOUR QUESTIONS HERE]

Please:
1. Identify each question
2. Determine if it's multiple choice or written (essay) type
3. Extract answer options for multiple choice questions
4. Identify the correct answer for multiple choice questions
5. Assign appropriate point values (1 point for simple MC, 2-5 for complex questions)
6. Output valid JSON that can be directly imported
```

### Example Output Structure

```json
{
  "questions": [
    {
      "question_type": "multiple_choice",
      "question_text": "What is the first rule of firearm safety?",
      "options": [
        "Always treat every firearm as if it is loaded",
        "Keep your finger off the trigger until ready to shoot",
        "Never point a firearm at anything you don't intend to destroy",
        "Be sure of your target and what is beyond it"
      ],
      "correct_answer": "Always treat every firearm as if it is loaded",
      "points": 1,
      "sort_order": 1,
      "is_active": true
    },
    {
      "question_type": "written",
      "question_text": "Explain why it's important to know what is beyond your target when shooting.",
      "options": null,
      "correct_answer": null,
      "points": 5,
      "sort_order": 2,
      "is_active": true
    }
  ]
}
```

### Step-by-Step Instructions

1. **Prepare Your Questions**
   - Open your document with test questions
   - Copy all questions (or sections you want to convert)
   - Ensure questions are clearly separated

2. **Use the Prompt**
   - Copy the prompt template above
   - Replace `[PASTE YOUR QUESTIONS HERE]` with your actual questions
   - Paste into ChatGPT

3. **Review and Edit**
   - Verify question types are correct
   - Check that all multiple choice options are included
   - Ensure correct answers match exactly (case-sensitive)
   - Adjust point values as needed

4. **Save the JSON**
   - Copy the JSON output from ChatGPT
   - Save it as a `.json` file (e.g., `test-questions.json`)
   - Go to the Knowledge Test Questions page
   - Use the "Import JSON" button

---

## Tips and Best Practices

### For Learning Articles

1. **Content Formatting**
   - Use HTML tags for structure: `<h2>`, `<h3>`, `<p>`, `<ul>`, `<li>`, `<strong>`, `<em>`
   - Avoid complex HTML (tables, iframes, etc.) - keep it simple
   - Line breaks will be preserved in paragraphs

2. **Categories**
   - Use consistent category names
   - Categories will be auto-created if they don't exist
   - Suggested categories: "Safety", "Regulations", "Training", "Maintenance", "Legal"

3. **Dedicated Types**
   - `"both"`: Available to all dedicated members (hunters and sport shooters)
   - `"hunter"`: Only for dedicated hunters
   - `"sport"`: Only for dedicated sport shooters
   - `null`: Available to all members (general content)

### For Knowledge Test Questions

1. **Multiple Choice Questions**
   - Minimum 2 options required
   - Maximum recommended: 4-5 options
   - Correct answer must match one option exactly (including capitalization)
   - Use clear, concise options

2. **Written Questions**
   - Set `question_type` to `"written"`
   - Set `options` and `correct_answer` to `null`
   - Assign higher point values (3-10 points) for written questions
   - These require manual marking by admins

3. **Point Values**
   - Simple MC questions: 1 point
   - Complex MC questions: 2-3 points
   - Written questions: 5-10 points
   - Total test points should align with passing score requirements

### General Tips

1. **Validation**
   - Always validate JSON before importing (use a JSON validator online)
   - Check for syntax errors (missing commas, quotes, etc.)
   - Ensure all required fields are present

2. **Batch Processing**
   - Convert documents in manageable chunks (10-20 articles/questions at a time)
   - Test with a small batch first before importing large files

3. **Backup**
   - Keep original documents as backup
   - Export existing content before bulk imports
   - Test imports on a development/staging environment first

4. **Error Handling**
   - If import fails, check the error message
   - Common issues:
     - Missing required fields
     - Invalid JSON syntax
     - Duplicate slugs (for articles)
     - Invalid question types

---

## Advanced: Custom ChatGPT Prompts

### For Complex Documents with Multiple Sections

```
I have a document with multiple sections that need to be converted into learning articles. Each section should become a separate article.

Document Structure:
- Title: [Document Title]
- Sections:
  [List sections or paste full content]

Please:
1. Identify each distinct topic/section
2. Create a unique article for each section
3. Use the section heading as the article title
4. Group related articles into appropriate categories
5. Generate excerpts from the first paragraph of each section
6. Format content with proper HTML tags
7. Output complete JSON ready for import
```

### For Converting Existing Test Banks

```
I have a test bank document with questions in various formats. Please convert them to the standardized JSON format.

Question Formats in Document:
[Describe or show examples of your question formats]

Please:
1. Identify each question
2. Extract question text
3. Identify question type (MC or written)
4. Extract all answer options for MC questions
5. Identify correct answers
6. Assign appropriate point values based on question complexity
7. Output valid JSON
```

---

## Troubleshooting

### Common Issues

1. **"Invalid JSON file" error**
   - Check JSON syntax with an online validator
   - Ensure all strings are properly quoted
   - Check for trailing commas

2. **"Missing required field" error**
   - Verify all required fields are present
   - Check field names match exactly (case-sensitive)

3. **"Duplicate slug" error (articles)**
   - Article titles must be unique
   - Change duplicate titles slightly

4. **Questions not importing**
   - Verify question_type is exactly "multiple_choice" or "written"
   - Check that options array has at least 2 items for MC questions
   - Ensure correct_answer matches an option exactly

### Getting Help

If you encounter issues:
1. Check the error message in the import modal
2. Validate your JSON using an online validator
3. Compare your JSON with the example templates
4. Try importing a single item first to test the format

---

## Example Files

Example JSON templates are available in:
`database/seeders/data/json-import-examples.json`

You can use these as reference when creating your own import files.
