<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithMapping;

class step1Export implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithMapping
{
    protected $rows;

    public function __construct(array $rows,$title)
    {
        $this->rows = $rows;
        $this->title = $title;
    }

    public function map($row): array
    {
        return [             
            $row['ganymed_id'],
            $row['pat_nr'],
            $row['previous_appoitment_date'],
            $row['next_appoitment_date'],
            $row['family_name'],          
            $row['first_name'],
            $row['birth_date'],
            $row['mobile_no'],
            $row['road'],
            $row['postal_code'],
            $row['place'],
            $row['mobile_no'],
            $row['insurance_number'],
            $row['name_matches'],
            $row['maching_info'],
            $row['99999 removed'],
            $row['match'],
            $row['result'],
            $row['app_id']
        ];
    }

    public function headings(): array
    {
        return [            
            'ganymed_id',
            'pat_nr',
            'previous_appoitment_date',
            'next_appoitment_date',
            'family_name',          
            'first_name',
            'birth_date',
            'mobile_no',
            'road',
            'postal_code',
            'place',
            'mobile_no',
            'insurance_number',
            'name_matches',
            'maching_info',
            '99999 removed',
            'match',
            'result',
            'app_id'];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->title;
    }

    
}