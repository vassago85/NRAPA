<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use Illuminate\Support\Str;

class WordDocumentConverter
{
    /**
     * Convert a Word document to learning articles JSON format.
     */
    public function convertToArticles(string $filePath, ?string $defaultCategory = null, ?string $dedicatedType = null): array
    {
        // Set output encoding
        Settings::setOutputEscapingEnabled(true);

        try {
            // Load the Word document
            $phpWord = IOFactory::load($filePath);
            
            $articles = [];
            $currentArticle = null;
            $currentCategory = $defaultCategory ?? 'General';
            
            // Iterate through sections
            foreach ($phpWord->getSections() as $section) {
                $elements = $section->getElements();
                
                foreach ($elements as $element) {
                    // Handle text runs
                    if (method_exists($element, 'getElements')) {
                        $textElements = $element->getElements();
                        $textContent = $this->extractTextFromElements($textElements);
                        
                        // Check if this looks like a heading (bold, larger font, etc.)
                        if ($this->isHeading($element, $textElements)) {
                            // If we have a current article, save it
                            if ($currentArticle && !empty($currentArticle['content'])) {
                                $articles[] = $currentArticle;
                            }
                            
                            // Start new article
                            $title = trim($textContent);
                            if (!empty($title)) {
                                $currentArticle = [
                                    'title' => $title,
                                    'category' => $currentCategory,
                                    'category_description' => null,
                                    'excerpt' => null,
                                    'content' => '',
                                    'is_published' => true,
                                    'is_featured' => false,
                                    'dedicated_type' => $dedicatedType,
                                    'published_at' => now()->toIso8601String(),
                                ];
                            }
                        } else {
                            // Add to current article content
                            if ($currentArticle !== null) {
                                $currentArticle['content'] .= $this->formatAsHtml($element, $textElements);
                            }
                        }
                    } else {
                        // Handle other element types
                        $text = $this->extractTextFromElement($element);
                        if (!empty($text) && $currentArticle !== null) {
                            $currentArticle['content'] .= $this->formatAsHtml($element, []);
                        }
                    }
                }
            }
            
            // Add the last article if exists
            if ($currentArticle && !empty($currentArticle['content'])) {
                $articles[] = $currentArticle;
            }
            
            // If no articles were created, create one from all content
            if (empty($articles)) {
                $fullText = $this->extractFullText($phpWord);
                if (!empty($fullText)) {
                    $articles[] = [
                        'title' => 'Imported Document',
                        'category' => $currentCategory,
                        'category_description' => null,
                        'excerpt' => Str::limit(strip_tags($fullText), 200),
                        'content' => $fullText,
                        'is_published' => true,
                        'is_featured' => false,
                        'dedicated_type' => $dedicatedType,
                        'published_at' => now()->toIso8601String(),
                    ];
                }
            }
            
            // Generate excerpts for articles that don't have them
            foreach ($articles as &$article) {
                if (empty($article['excerpt']) && !empty($article['content'])) {
                    $article['excerpt'] = Str::limit(strip_tags($article['content']), 200);
                }
                // Clean up content
                $article['content'] = $this->cleanHtml($article['content']);
            }
            
            return [
                'articles' => $articles,
                'export_date' => now()->toIso8601String(),
                'version' => '1.0',
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to convert Word document: ' . $e->getMessage());
        }
    }
    
    /**
     * Extract text from elements.
     */
    protected function extractTextFromElements(array $elements): string
    {
        $text = '';
        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $text .= $element->getText();
            } elseif (method_exists($element, 'getElements')) {
                $text .= $this->extractTextFromElements($element->getElements());
            }
        }
        return $text;
    }
    
    /**
     * Extract text from a single element.
     */
    protected function extractTextFromElement($element): string
    {
        if (method_exists($element, 'getText')) {
            return $element->getText();
        }
        if (method_exists($element, 'getElements')) {
            return $this->extractTextFromElements($element->getElements());
        }
        return '';
    }
    
    /**
     * Check if element is a heading.
     */
    protected function isHeading($element, array $textElements): bool
    {
        // Check for bold text (often used for headings)
        foreach ($textElements as $textElement) {
            if (method_exists($textElement, 'getFontStyle')) {
                $font = $textElement->getFontStyle();
                if ($font && $font->isBold()) {
                    $text = $this->extractTextFromElement($textElement);
                    // If it's a short line (likely a heading)
                    if (strlen($text) < 100 && !empty(trim($text))) {
                        return true;
                    }
                }
            }
        }
        
        // Check if it's a text run with specific formatting
        if (method_exists($element, 'getFontStyle')) {
            $font = $element->getFontStyle();
            if ($font && ($font->isBold() || $font->getSize() > 12)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Format element as HTML.
     */
    protected function formatAsHtml($element, array $textElements): string
    {
        $html = '';
        $text = $this->extractTextFromElements($textElements);
        
        if (empty(trim($text))) {
            return '';
        }
        
        // Check if it's a heading
        if ($this->isHeading($element, $textElements)) {
            $html .= '<h2>' . htmlspecialchars(trim($text)) . '</h2>';
        } else {
            // Check for formatting
            $isBold = false;
            $isItalic = false;
            
            foreach ($textElements as $textElement) {
                if (method_exists($textElement, 'getFontStyle')) {
                    $font = $textElement->getFontStyle();
                    if ($font) {
                        if ($font->isBold()) $isBold = true;
                        if ($font->isItalic()) $isItalic = true;
                    }
                }
            }
            
            $formattedText = htmlspecialchars($text);
            if ($isBold) $formattedText = '<strong>' . $formattedText . '</strong>';
            if ($isItalic) $formattedText = '<em>' . $formattedText . '</em>';
            
            $html .= '<p>' . $formattedText . '</p>';
        }
        
        return $html;
    }
    
    /**
     * Extract full text from document.
     */
    protected function extractFullText($phpWord): string
    {
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            $elements = $section->getElements();
            foreach ($elements as $element) {
                $text .= $this->extractTextFromElement($element) . "\n";
            }
        }
        return trim($text);
    }
    
    /**
     * Clean HTML content.
     */
    protected function cleanHtml(string $html): string
    {
        // Remove empty paragraphs
        $html = preg_replace('/<p>\s*<\/p>/', '', $html);
        // Remove multiple newlines
        $html = preg_replace('/\n{3,}/', "\n\n", $html);
        // Trim whitespace
        return trim($html);
    }
}
