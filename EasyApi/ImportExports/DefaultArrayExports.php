<?php
namespace Modules\Ynotz\EasyAdmin\ImportExports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DefaultArrayExports implements FromArray, WithHeadings
{
    protected $array = [];
    private $colsFormat;
    private $colTitles = null;

    public function __construct($array, $colsFormat, $colTitles = [])
    {
        $this->array = [];
        foreach ($array as $row) {
            $temprow = [];
            foreach ($colsFormat as $col) {
                $colstr_x = str_replace('.', '', $col);
                if (strlen($col) != strlen($colstr_x)) {
                    $t = explode('.', $col);
                    $rel = $t[0];
                    $field = $t[1];
                    // if (count($row[$rel]) > 0) {
                    //     dd($rel, $row[$rel], $field);
                    //     $temprow[$col] = ($row[$rel][0])[$field];
                    // } else {
                        $temprow[$col] = ($row[$rel])[$field];
                    // }
                } else {
                    $temprow[$col] = $row[$col];
                }
            }
            $this->array[] = $temprow;
        }
        $this->colsFormat = $colsFormat;
        $this->colTitles = $colTitles;
    }

    public function array(): array
    {
        return $this->array;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->colsFormat as $cf) {
            $headings[] = isset($this->colTitles[$cf])
                ? ucfirst($this->colTitles[$cf])
                : ucfirst($cf);
        }
        return $headings;
    }
}
?>
