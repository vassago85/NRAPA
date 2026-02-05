# Learning Center Structure Guide

## Recommended Structure

For your two main learning modules (Dedicated Hunter and Sport Shooter), use this hierarchy:

```
Learning Modules (dedicated_type)
├── Categories (Chapters)
│   ├── Articles (Lessons/Sub-items)
│   ├── Articles (Lessons/Sub-items)
│   └── Articles (Lessons/Sub-items)
└── Categories (Chapters)
    ├── Articles (Lessons/Sub-items)
    └── Articles (Lessons/Sub-items)
```

## Structure Breakdown

### **Categories = Chapters**
- Each category represents a chapter/module section
- Examples:
  - "Chapter 1: Firearm Safety"
  - "Chapter 2: Legal Requirements"
  - "Chapter 3: Hunting Regulations"
  - "Chapter 4: Sport Shooting Rules"

### **Articles = Lessons/Sub-items**
- Each article is a lesson or topic within a chapter
- Examples:
  - "Chapter 1: Firearm Safety" → "Basic Safety Rules"
  - "Chapter 1: Firearm Safety" → "Safe Storage Practices"
  - "Chapter 1: Firearm Safety" → "Transportation Guidelines"

## Recommended Setup

### For Dedicated Hunter Module:

**Categories (Chapters):**
1. **Chapter 1: Firearm Safety** (`dedicated_type: hunter`)
   - Article: Basic Safety Rules
   - Article: Safe Storage Practices
   - Article: Transportation Guidelines

2. **Chapter 2: Legal Requirements** (`dedicated_type: hunter`)
   - Article: Firearms Control Act Overview
   - Article: Licensing Requirements
   - Article: Compliance Obligations

3. **Chapter 3: Hunting Regulations** (`dedicated_type: hunter`)
   - Article: Provincial Regulations
   - Article: Protected Species
   - Article: Hunting Seasons

### For Dedicated Sport Shooter Module:

**Categories (Chapters):**
1. **Chapter 1: Firearm Safety** (`dedicated_type: sport`)
   - Article: Range Safety Rules
   - Article: Equipment Safety Checks
   - Article: Competition Safety Protocols

2. **Chapter 2: Legal Requirements** (`dedicated_type: sport`)
   - Article: Firearms Control Act Overview
   - Article: Licensing Requirements
   - Article: Compliance Obligations

3. **Chapter 3: Competition Rules** (`dedicated_type: sport`)
   - Article: IPSC Rules
   - Article: IDPA Rules
   - Article: Local Competition Guidelines

### Shared Content:

**Categories (Chapters):**
- **General Safety** (`dedicated_type: both` or `null`)
  - Article: Universal Safety Rules
  - Article: Emergency Procedures

## Best Practices

### 1. **Naming Conventions**

**Categories (Chapters):**
- Use clear chapter names: "Chapter 1: [Topic]", "Chapter 2: [Topic]"
- Or descriptive names: "Firearm Safety", "Legal Requirements"
- Keep names concise but descriptive

**Articles (Lessons):**
- Use specific, descriptive titles
- Examples:
  - ✅ "Basic Safety Rules"
  - ✅ "Safe Storage Practices"
  - ❌ "Safety" (too vague)
  - ❌ "Chapter 1 Content" (not descriptive)

### 2. **Dedicated Type Assignment**

**Option A: Set at Category Level (Recommended)**
- Set `dedicated_type` on the Category
- Articles inherit from category (can override if needed)
- Easier to manage

**Option B: Set at Article Level**
- More granular control
- Useful if a chapter has mixed content

**Recommended:** Use Category-level assignment for consistency

### 3. **Sort Order**

**Categories:**
- Use `sort_order` to control chapter sequence
- Example: 10, 20, 30 (allows inserting chapters later)

**Articles:**
- Use `sort_order` within each category
- Example: 1, 2, 3 for lesson sequence

### 4. **Content Organization**

**For Word Document Imports:**
- Structure your Word doc with clear headings
- Each major heading = Category (Chapter)
- Each sub-heading or section = Article (Lesson)
- The converter will automatically detect and create this structure

## Example Structure

```
Dedicated Hunter Module (dedicated_type: hunter)
│
├── Chapter 1: Firearm Safety (sort_order: 10)
│   ├── Lesson 1.1: Basic Safety Rules (sort_order: 1)
│   ├── Lesson 1.2: Safe Storage (sort_order: 2)
│   └── Lesson 1.3: Transportation (sort_order: 3)
│
├── Chapter 2: Legal Requirements (sort_order: 20)
│   ├── Lesson 2.1: Firearms Control Act (sort_order: 1)
│   ├── Lesson 2.2: Licensing Process (sort_order: 2)
│   └── Lesson 2.3: Compliance (sort_order: 3)
│
└── Chapter 3: Hunting Regulations (sort_order: 30)
    ├── Lesson 3.1: Provincial Rules (sort_order: 1)
    └── Lesson 3.2: Protected Species (sort_order: 2)

Dedicated Sport Shooter Module (dedicated_type: sport)
│
├── Chapter 1: Firearm Safety (sort_order: 10)
│   ├── Lesson 1.1: Range Safety (sort_order: 1)
│   └── Lesson 1.2: Equipment Checks (sort_order: 2)
│
└── Chapter 2: Competition Rules (sort_order: 20)
    ├── Lesson 2.1: IPSC Rules (sort_order: 1)
    └── Lesson 2.2: IDPA Rules (sort_order: 2)
```

## Importing from Word Documents

When importing Word documents:

1. **Structure your document:**
   ```
   Chapter 1: Firearm Safety
   
   Basic Safety Rules
   [Content here...]
   
   Safe Storage Practices
   [Content here...]
   
   Chapter 2: Legal Requirements
   
   Firearms Control Act Overview
   [Content here...]
   ```

2. **Set dedicated_type when importing:**
   - Select "Dedicated Hunter" or "Dedicated Sport Shooter"
   - All imported categories/articles will have that type

3. **Review and adjust:**
   - Check that chapters were detected correctly
   - Verify articles are properly organized
   - Adjust sort_order if needed

## Tips

1. **Use consistent naming:** Keep chapter/article naming consistent across modules
2. **Set sort_order:** Use increments of 10 (10, 20, 30) to allow easy reordering
3. **Use descriptions:** Add category descriptions to explain what the chapter covers
4. **Featured articles:** Mark important lessons as "featured" for easy discovery
5. **Reading time:** System auto-calculates, but you can adjust if needed

## Current System Features

✅ Categories support `dedicated_type` filtering
✅ Articles inherit category's `dedicated_type` (can override)
✅ Sort order for both categories and articles
✅ Automatic content filtering based on user's dedicated status
✅ Word document import automatically creates this structure
✅ JSON import/export for bulk management
