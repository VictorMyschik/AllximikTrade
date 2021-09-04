<?php

namespace App\Helpers;

use App\Models\MrCountry;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\BaseReader;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MtExcelHelperBase
{
  public const MAX_ROWS = 25000;
  public const MAX_COLUMNS = 'CZ';
  public const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

  private static $excel = null;

  /**
   * Создаёт новый ексель (с возжным шаблоном) для заполнения данными
   *
   * @param string|null $template_real_filename
   * @return Spreadsheet
   */
  protected static function excel(?string $template_real_filename = null): Spreadsheet
  {
    self::$excel = null;

    if($template_real_filename)
    {
      //load
      if(file_exists($template_real_filename))
      {
        try
        {
          self::$excel = IOFactory::load($template_real_filename);
        } catch (Exception $ex)
        {
          mt_exception('$template_real_filename file processing error: ' . $ex->getMessage());
        }
      }
      else
      {
        mt_exception('$template_real_filename file not found');
      }
    }

    if(is_null(self::$excel)) //new spreadsheet
    {
      self::$excel = new Spreadsheet();

      self::$excel->getProperties()
        ->setCreator('TWS.BY')
        ->setTitle('Выгрузка в Excel');
    }

    return self::$excel;
  }

  protected static function getExcel(): Spreadsheet
  {
    if(!self::$excel)
    {
      self::excel();
    }

    return self::$excel;
  }

  /**
   * Загружает из файла лист.
   * Загружаются только данные. пустые ячейки игнорируются.
   *
   * @param string $filename
   * @param int $sheet_number
   * @return Worksheet
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
   */
  public static function LoadWorksheet(string $filename, int $sheet_number = 0): Worksheet
  {
    $excel_filetype = IOFactory::identify($filename);
    $excel_reader = IOFactory::createReader($excel_filetype);

    if($excel_reader instanceof BaseReader)
    {
      $excel_reader->setReadDataOnly(true);
      $excel_reader->setIncludeCharts(false);
      //$excel_reader->setReadFilter(MtExcelReaderHelper::gi());
    }

    $spreadsheet = $excel_reader->load($filename);
    $sheet = $spreadsheet->getSheet($sheet_number);

    return $sheet;
  }

  public static function EndsWith(string $haystack, string $needle): bool
  {
    $length = strlen($needle);

    if($length == 0)
    {
      return false;
    }

    return (substr($haystack, -$length) === $needle);
  }

  /**
   * сохраняет текущий ексель в файл и отдаёт этот файл на стандартный вывод с именем $filename.
   * Если ещё не установены, то устанавливает заголовки Content-Type и Content-Disposition
   *
   * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
   */
  protected static function write(?string $filename = null)
  {
    if(self::$excel)
    {
      //set headers (if not already set)
      $has_content_type = false;
      $has_content_disposition = false;
      foreach (headers_list() as $http_header)
      {
        if(MrStringUtils::StartsWith($http_header, 'Content-Type:'))
        {
          $has_content_type = true;
        }

        if(MrStringUtils::StartsWith($http_header, 'Content-Disposition:'))
        {
          $has_content_disposition = true;
        }
      }

      if(!$has_content_type)
      {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      }

      if(!$has_content_disposition)
      {
        $filename = $filename ?: 'ExcelExport.xlsx';

        if(!MrStringUtils::EndsWith($filename, '.xlsx'))
        {
          $filename = $filename . '.xlsx';
        }

        $filename = MrStringUtils::SanitizeFileName($filename);

        header('Content-Disposition: attachment;filename="' . $filename . '"');
      }

      //write outout
      $writer = IOFactory::createWriter(self::$excel, 'Xlsx');
      $writer->save('php://output');
    }
  }

  /**
   * Читает дату из ячейки Excel, независимо от типа ячейки (дата, число или строка)
   *
   * @param $excelValue
   * @return MrDateTime|null
   * @throws Exception
   */
  public static function dateValue($excelValue): ?MrDateTime
  {
    $datetime = null;

    if($excelValue)
    {
      if(is_numeric($excelValue))
      {
        try
        {
          $datetime = Date::excelToDateTimeObject($excelValue, MrDateTime::getDefaultTimezone());
        } catch (Exception $ex)
        {
          $datetime = null;
        }
      }

      //mt_dump('$datetime', $datetime);

      if($datetime)
      {
        $datetime = new MrDateTime($datetime);
      }
      else
      {
        try
        {
          $datetime = new MrDateTime($excelValue);
        } catch (Exception $ex)
        {
          $datetime = null;
        }
      }
    }

    return $datetime;
  }

  /**
   * Читает первую строку и строит карту сопоставления номеров столбцов и названий колонки и первой строки
   *
   * @param Worksheet $sheet
   * @param int $row_num
   * @param string $max_column defaults to 'CW' = 100 cells
   *
   * @return array 'column index' => 'header'
   */
  public static function BuildFirstRowHeaderMap(Worksheet $sheet, $row_num = 1, $max_column = 'CW'): array
  {
    $header_row_array = $sheet->rangeToArray('A' . $row_num . ':' . $max_column . $row_num, null, false, true, true);
    $data_map = array_filter($header_row_array[1]);

    //mt_dump_d('$data_map', $data_map);

    return $data_map;
  }

  /**
   * Заполняет первую строку и строит карту сопоставления номеров столбцов и названий колонки
   *
   * @param Worksheet $sheet
   * @param array $header title => width
   * @param int $row_num
   * @return array 'column index' => 'header'
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public static function SetFirstRowHeader(Worksheet $sheet, array $header, $row_num = 1): array
  {
    $column = 'A';
    $data_map = array();

    if(count($header) < 1)
    {
      mt_exception('header is empty');
    }

    foreach ($header as $key => $value)
    {
      if(is_numeric($key))
      {
        $title = $value;
        $width = 0;
      }
      else
      {
        $title = $key;
        $width = $value;
      }

      $data_map[$column] = $title;

      $sheet->setCellValueExplicit($column . $row_num, $title, DataType::TYPE_STRING);

      if($width)
      {
        $sheet->getColumnDimension($column)->setWidth($width);
      }

      $column++;
    }

    $header_range = 'A' . $row_num . ':' . array_key_last($data_map) . $row_num;

    $sheet->getStyle($header_range)->getFont()->setBold(true);
    $sheet->getStyle($header_range)->getAlignment()->setHorizontal('center');

    $sheet->getStyle($header_range)->getFill()
      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()->setARGB('f2f2f2');

    $sheet->getStyle(
      $header_range
    )->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);


    $sheet->freezePane('A2');

    //mt_dump_d('$data_map', $data_map);

    return $data_map;
  }

  /**
   * Читает и выполняет первичную обработку данных из строки Excel.
   *
   * Елси ни в однйо ячейке не оказалось данных, возвращает null (для пропуска пустых строк).
   *
   * @param Row $excel_row
   * @param array $data_map
   * @return array|null
   */
  protected static function ReadRowData(Row $excel_row, ?array $data_map = null): ?array
  {
    $row = array();

    //read all the data from row
    $row_empty = true;
    foreach ($excel_row->getCellIterator() as $excel_cell)
    {
      $value = trim($excel_cell->getValue());

      $row[$excel_cell->getColumn()] = $value;

      if($value)
      {
        $row_empty = false;
      }
    }

    //ignoring empty rows in excel
    if($row_empty)
    {
      return null;
    }

    //fill missing cells with NULL values
    if(is_array($data_map))
    {
      foreach ($data_map as $column => $header)
      {
        if(!isset($row[$column]))
        {
          $row[$column] = null;
        }
      }
    }

    return $row;
  }

  #region Хелперы для чтения специфических данных из колонок с проверкой по справочникам

  /**
   * Строковое значение
   *
   * @param        $value
   * @param int $max_length максимальная длина строки
   * @param array $errors массив с ошибками
   * @param string $cell_name
   * @return null|string
   */
  protected static function ReadString($value, int $max_length, array &$errors, string $cell_name): ?string
  {
    if(!$value)
    {
      return null;
    }

    $value = (string)$value;

    $length = mb_strlen($value);
    if($length > $max_length)
    {
      $errors[] = ($cell_name . "максимальная длина строки $max_length, а в ячейке $length.");

      return null;
    }

    return $value;
  }

  /**
   * Целочисленное значение
   *
   * @param        $value
   * @param array $errors массив с ошибками
   * @param string $cell_name
   *
   * @return null|int
   */
  protected static function ReadInt($value, array &$errors, string $cell_name): ?int
  {
    if(is_string($value))
    {
      $value = trim($value);
    }

    if(!strlen($value))
    {
      return null;
    }

    if(!is_numeric($value))
    {
      $errors[] = ($cell_name . "неверное целочисленное значение '$value'");
      return null;
    }

    $value = (int)$value;

    return $value;
  }


  /**
   * Страна
   *
   * @param        $value
   * @param array $errors массив с ошибками
   * @param string $cell_name
   * @return int|null
   */
  protected static function ReadCountry($value, array &$errors, string $cell_name): ?int
  {
    if(strlen(trim($value))) //'00' code could here, so using strlen
    {
      if($value == 'OO' || $value == 'OО' || $value == 'ОO' || $value == 'ОО')
      {
        $value = '00';
      }

      $country = MrCountry::loadBy($value, 'Code') ?: (MrCountry::loadBy($value, 'NameRus') ?: MrCountry::loadBy($value, 'NameEng'));

      if($country)
      {
        $value = $country->id();
      }
      else
      {
        dd('$errors[] = mt_render($cell_name . "страна \'$value\' не найдена. Перейти в <a href=\"/tws/references/countries\" target=\'_blank\'>Классификатор стран мира</a>.");');
        $errors[] = ($cell_name . "страна '$value' не найдена. Перейти в <a href=\"/tws/references/countries\" target='_blank'>Классификатор стран мира</a>.");
        return null;
      }
    }
    else
    {
      $value = null;
    }

    return $value;
  }

  /**
   * Стоимость или цена в валюте
   *
   * @param        $value
   * @param array $errors массив с ошибками
   * @param string $cell_name
   * @return float|null
   */
  protected static function ReadSum($value, array &$errors, string $cell_name): ?float
  {
    if(!$value)
    {
      return null;
    }

    if(!MtFloatHelper::canConvert($value))
    {
      $errors[] = $cell_name . 'неверное значение стоимости.';
      return null;
    }

    $value = MtFloatHelper::toFloat($value);

    if($value <= 0)
    {
      $errors[] = $cell_name . 'значение стоимости должно быть больше нуля.';
      return null;
    }

    return $value;
  }

  /**
   * Количество
   *
   * @param        $value
   * @param array $errors массив с ошибками
   * @param string $cell_name
   * @return float|null
   */
  protected static function ReadAmount($value, array &$errors, string $cell_name): ?float
  {
    if(!$value)
    {
      return null;
    }

    if(!MtFloatHelper::canConvert($value))
    {
      $errors[] = $cell_name . 'неверное значение количества.';
      return null;
    }

    $value = MtFloatHelper::toFloat($value);

    if($value <= 0)
    {
      $errors[] = $cell_name . 'значение количества должно быть больше нуля.';
      return null;
    }

    return $value;
  }

  /**
   * Дата
   *
   * @param        $value
   * @param array $errors массив с ошибками
   * @param string $cell_name
   * @return string|null YYYY-MM-DD
   * @throws Exception
   */
  protected static function ReadDate($value, array &$errors, string $cell_name): ?string
  {
    if(!$value)
    {
      return null;
    }

    $date = self::dateValue($value);

    if(!$date)
    {
      $errors[] = $cell_name . "неверное значение даты '$value'.";
      return null;
    }

    return $date;
  }

  /**
   * Проверяется наличие обязательных к заполнению полей
   *
   * @param array $row_data
   * @param array $required_columns
   * @param array $errors
   * @param string $row_name
   */
  protected static function CheckRequiredValues(array $row_data, array $required_columns, array &$errors, string $row_name)
  {
    $missing_columns = array();

    foreach ($required_columns as $column)
    {
      if(isset($row_data[$column]) && strlen($row_data[$column]))
      {
        continue;
      }

      $missing_columns[] = $column;
    }

    if(count($missing_columns))
    {
      $errors[] = $row_name . 'отсутствуют следующие обязательные значения: ' . implode(', ', $missing_columns);
    }
  }

  /**
   * Проверяется что заполнены все поля из набора или ни одного.
   *
   * @param array $row_data
   * @param array $columns
   * @param array $errors
   * @param string $row_name
   */
  protected static function CheckAllOrNothing(array $row_data, array $columns, array &$errors, string $row_name)
  {
    if(count($columns) < 2)
    {
      return;
    }

    $message = $row_name . '"' . implode('", "', $columns) . '" - должны быть заполнены или все перечисленные колонки, или ни одной.';

    $has_value = false;
    foreach ($columns as $column)
    {
      if(strlen($row_data[$column] ?? ''))
      {
        $has_value = true;
      }
      else
      {
        if($has_value)
        {
          $errors[] = $message;
          break;
        }
      }
    }
  }

  #endregion
}
