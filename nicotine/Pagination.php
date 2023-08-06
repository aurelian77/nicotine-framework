<?php
declare(strict_types=1);

namespace nicotine;

/**
| Pagination class.
*/
final class Pagination extends Dispatcher {

    public int $perPage = 20;

    public string $info = 'Page %1$d from %2$d';
    
    public string $previousPage = 'Previous page';
    
    public string $nextPage = 'Next page';

    public int $currentPage = 1;
    
    public int $totalRecords;
    
    public string $url;
    
    public function __construct(int $totalRecords)
    {
        $this->totalRecords = $totalRecords;
        
        if (isset($_GET['page']) && is_natural($_GET['page'])) {
            $this->currentPage = intval($_GET['page']);
        } 
        
        $this->url = $_SERVER['REQUEST_URI'];
    }
    
    public function getLimitStart(): int
    {
        return (($this->currentPage - 1) * $this->perPage);
    }
    
    public function getLimitEnd(): int
    {
        return $this->perPage;
    }

    public function generateUrl($page): string
    {
        $url = $this->url;

        $pattern = '/([\?\&]{1}page\=)([0-9]+)/i';

        if (preg_match($pattern, $url)) {
            $url = preg_replace($pattern, '${1}'.$page, $url);
        } else {
            if (str_contains($url, '&') || str_contains($url, '?')) {
                $url = $url .'&page='. $page;
            } else {
                $url = $url .'?page='. $page;
            }
        }

        return $url;
    }
    
    public function showNavigation(): void
    {
        print '<div class="pagination">';
            if ($this->currentPage > 1) {
                ?>
                <a href="<?php print $this->generateUrl($this->currentPage - 1); ?>" class="pagination-link">
                    <?php print $this->previousPage; ?>
                </a>
                <?php
            }
            ?>
            <span class="pagination-current">
                <?php printf($this->info, $this->currentPage, ceil($this->totalRecords / $this->perPage)); ?>
            </span>
            <?php
 
            if ($this->totalRecords > $this->perPage * $this->currentPage) {
                ?>
                <a href="<?php print $this-> generateUrl($this->currentPage + 1); ?>" class="pagination-link">
                    <?php print $this->nextPage; ?>
                </a>
                <?php
            }
        print '</div>';
    }

}
