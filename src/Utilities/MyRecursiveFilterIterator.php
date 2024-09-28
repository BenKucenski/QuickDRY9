<?php

namespace Bkucenski\Quickdry\Utilities;

use RecursiveFilterIterator;

/**
 *
 */
class MyRecursiveFilterIterator extends RecursiveFilterIterator
{
    /**
     * @return bool
     */
    public function accept(): bool
    {
        $filename = $this->current()->getFilename();
        // Skip hidden files and directories.
        if ($filename[0] === '.') {
            return false;
        }
        return true;
    }
}