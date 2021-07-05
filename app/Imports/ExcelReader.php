<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ExcelReader implements ToCollection, WithChunkReading
{
    use Importable;
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        return $collection;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
