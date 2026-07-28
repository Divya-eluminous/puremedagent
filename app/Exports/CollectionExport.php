<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;


class CollectionExport implements FromCollection, WithHeadings,ShouldAutoSize
{
    use Exportable;
    public function __construct($collection,$headings){
        
        $this->headings = $headings;
        $this->collection = $collection;
    }

    public function collection()
    {
        return collect($this->collection);
    }

    public function headings(): array
    {
        return $this->headings;
    }

}