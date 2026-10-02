<?php
/**
 * DocumentLink View helper
 * 
 * @category   Lelam
 * @package    Lelam\ViewHelper
 */

namespace Lelam\ViewHelper;

use Web\View\ViewHelper;


class DocumentLink extends ViewHelper
{
    const MAX_LENGTH = 30;
    
    /**
     * get document link
     * 
     * @param \stdClass|array|string $record
     * @param string $base
     * @return string
     */
    public function documentLink($record, $base = 'documents', $length = null)
    {
        if (is_string($record)) {
            $document = (array) unserialize(base64_decode($record));
        } else {
            $document = (array) $record;
        }
        $icons = [
            'jpg' => 'la-image',
            'pdf' => 'la-file-pdf-o',
            'doc' => 'la-file-text',
        ];
        
        $titles = [
            'jpg' => 'View Image',
            'pdf' => 'View PDF',
        ];
        
        $name = $document['original_filename'];
        if (null === $length) {
            $length = self::MAX_LENGTH;
        }
        if ($length > 0) {
            if (strlen($name) > $length) {
                $name = '..' . substr($document['original_filename'], (-1 * $length));
            }
        } elseif (0 === $length) {
            $name = '';
        }
        
        $parts = explode('.', $document['filename']);
        $extension = strtolower(end($parts));
        if (isset($icons[$extension])) {
            $icon = $icons[$extension];
            $title = $titles[$extension];
        } else {
            $icon = $icons['doc'];
            $title = 'View Document';
        }

        $file = 'assets/' . $base . '/' . $document['file_path'] . '/' . $document['filename'];
        $link = '<a href="' . $this->getRegistry('baseUrl') . $file . '" title="' . $title . '"'
              . ' target="_blank"><i class="la ' . $icon . ' red"> </i> '
              . $this->escape($name) . '</a>';
        
        return $link;
    }
    
}
