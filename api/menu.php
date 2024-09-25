<?php
class Menu
{
    private $menuFileName;
    public function __construct($file)
    {
        $this->menuFileName = $file;
    }

    function get($isSysAdmin)
    {
        function cmp($a, $b)
        {
            return $a->order > $b->order;
        }

        $menuList = [];
        if (($file = fopen($this->menuFileName, "r")) !== FALSE) {
            $isFirstLine = true;
            while (($line = fgetcsv($file, 1000, ",")) !== FALSE) {
                if (!$isFirstLine) {
                    if ($isSysAdmin || $line[4] === '0') {
                        $menu = new stdClass();
                        $menu->menu = $line[0];
                        $menu->route = $line[1];
                        $menu->order = $line[2];
                        $menu->icon = $line[3];
                        $menu->sysAdmin = $line[4] === '1';
                        $menu->columns = explode("-", $line[5]);

                        $menuList[] = $menu;
                    }
                }
                $isFirstLine = false;
            }
            fclose($file);
            usort($menuList, "cmp");
            return $menuList;
        }
    }
}
