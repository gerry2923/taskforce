<?php
namespace taskforce\converver;

use taskforce\exceptions\ConverterException;
use SplFileInfo;
use SplFileObject;

class CsvSqlConverter {

  protected array $filesToConvert; // сохраняем объекты класса SplFileInfo

  /**
   * CsvSqlConverter constructor.
   * @param $inputDir
   * @throws ConverterException
   */
  public function __construct(string $inputDir)
  {
    if(!is_dir($inputDir))
    {
      throw new ConverterException("Указанная директория не существует");
    }

    $this->loadCsvFiles($inputDir);
    
  }
  // добавить в массив объекты SplFileInfo, которые хранят всю инфу о файле
  private function loadCsvFiles(string $inputDir): void
  {
    foreach( new \DirectoryIterator($inputDir) as $file )
    {
    
      if($file->getExtension() == 'csv')
      {
        $this->filesToConvert[] = $file->getFileInfo();
      }
    }
  }

  public function convertFiles(string $outputDir):array
  { // кладем в массив имена созданных файлов
    $result = [];

    foreach($this->filesToConvert as $file)
    {
      $result[] = $this->convertFile($file, $outputDir);
    }

    return $result;
  }

  private function convertFile(SplFileInfo $file, string $outputDir): string
  {

    $fileObject = new SplFileObject($file->getRealPath());
    $fileObject->setFlags(SplFileObject::READ_CSV);

    $columns = $fileObject->fgetcsv(); // возвращает массив из слов строки
    
    $values = [];

    while(!$fileObject->eof()) // пока не достигнет конца строки
    {
      $values[] = $fileObject->fgetcsv();
    }

    $tableName = $file->getBasename('.csv');
    $this->columnNameCorrection($columns, $tableName);

    $sqlContent = $this->getSqlContent($tableName, $columns, $values);

    return $this->saveSqlContent($tableName, $outputDir, $sqlContent);
    // return " X ";
  }

  private function getSqlContent(string $tableName, array $columns, array $values): string
  { // $values  - двумерный массив

    $columnsString = implode(", ", $columns); // строка с колонками таблицы (соединяет в строку элементы массива)
    $sql = "INSERT INTO $tableName ($columnsString) VALUES";

    foreach ($values as $row)
    {
      array_walk($row, function (&$value)  // применяет заданную пользователем функцию к каждому элементу массива
      {
        $value = addslashes($value);
        $value = "'$value'";
      });

      $sql .="(" .implode(', ', $row) . "), ";

    }
    $sql = substr($sql, 0, -2);

    return $sql;
  }

  private function saveSqlContent(string $tableName, string $outputDir, string $content):string
  {
    if(!is_dir($outputDir))
    {
      throw new ConverterException("Директории для выходных файлов не существует");
    }

    $filename = $outputDir . DIRECTORY_SEPARATOR . $tableName . '.sql';
    file_put_contents($filename, $content);

    return $filename;
  }

  private function columnNameCorrection(array &$columns, string $tableName):void
  {
    $tableName = $this->checkEndsOfWords("ies", "y", $tableName);

    foreach ($columns as &$word)
    {
      $word  = $tableName . "_" . $word;
    }
  }
  // создать отдельный класс, который конвертирует слова из множественного числа в единственное  и обратно.
  // использовать его вместо функйии checkEndsOfWords
  private function checkEndsOfWords(string $curentEnd, string $replacement, string &$word):string
  {
    $pattern = "/".$curentEnd."/";
    $check_pattern = "/(".$curentEnd.")$/";

    if(preg_match($check_pattern, $word))
    {
      $word = preg_replace($pattern, $replacement, $word);
    }

    return $word;
  }
}


