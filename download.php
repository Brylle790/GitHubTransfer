<?php
require 'vendor/autoload.php'; // Include the PhpSpreadsheet library
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
require 'db.php'; // Include database connection

// Validate POST data
if (!isset($_POST['year']) || !isset($_POST['month'])) {
    die('Invalid request.');
}

$year = (int)$_POST['year'];
$month = (int)$_POST['month'];

// Fetch sales data for the specified month
$query = "SELECT id, name, email, REPLACE(products, '<br>', '') as products, total_price, order_status, completed_at FROM sales WHERE YEAR(completed_at) = ? AND MONTH(completed_at) = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $year, $month);
$stmt->execute();
$result = $stmt->get_result();
$sales = $result->fetch_all(MYSQLI_ASSOC);

if (empty($sales)) {
    die('No sales data found for the specified month.');
}

// Calculate total prices for the month
$totalQuery = "SELECT SUM(total_price) as total_price_sum FROM sales WHERE YEAR(completed_at) = ? AND MONTH(completed_at) = ?";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->bind_param('ii', $year, $month);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalSum = $totalResult->fetch_assoc()['total_price_sum'];

// Parse products to calculate total items purchased by size
$productSummary = [];
foreach ($sales as $sale) {
    $products = explode(')', $sale['products']);
    foreach ($products as $product) {
        if (trim($product) === '') {
            continue;
        }
        $product .= ')'; // Add back the closing parenthesis
        preg_match('/^(.*)\((.*),\s*(\d+)\)$/', $product, $matches);
        if (count($matches) === 4) {
            $productName = trim($matches[1]);
            $size = trim($matches[2]);
            $quantity = (int)$matches[3];

            $key = $productName . '|' . $size;
            if (!isset($productSummary[$key])) {
                $productSummary[$key] = ['name' => $productName, 'size' => $size, 'total' => 0];
            }
            $productSummary[$key]['total'] += $quantity;
        }
    }
}

// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Add header row for sales data
$headers = array_keys($sales[0]);
foreach ($headers as $columnIndex => $header) {
    $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1) . '1';
    $sheet->setCellValue($cell, $header);

    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
}

// Add sales data with products on new lines
$rowNumber = 2;
foreach ($sales as $row) {
    foreach (array_values($row) as $columnIndex => $value) {
        if ($headers[$columnIndex] === 'products') {
            // Add newline between products
            $value = str_replace(')', ")\n", $value);
            $value = trim($value); // Remove trailing newline
        }

        $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1) . $rowNumber;
        $sheet->setCellValue($cell, $value);

        // Enable text wrapping for the products column
        if ($headers[$columnIndex] === 'products') {
            $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
        }
    }
    $rowNumber++;
}


// Add total prices row
$sheet->setCellValue("A$rowNumber", "Total");
$sheet->setCellValue("E$rowNumber", $totalSum);
$sheet->getStyle("E$rowNumber")
    ->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()
    ->setARGB('FF8DB4E2'); // Light blue background

// Remove borders from H Column
$sheet->getStyle("H1:H$rowNumber")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);


// Add summary header
$rowNumber = 1;
$sheet->setCellValue("I$rowNumber", "Product Name");
$sheet->setCellValue("J$rowNumber", "Size");
$sheet->setCellValue("K$rowNumber", "Total Items Purchased");

// Add product summary
$rowNumber++;
foreach ($productSummary as $summary) {
    $sheet->setCellValue("I$rowNumber", $summary['name']);
    $sheet->setCellValue("J$rowNumber", $summary['size']);
    $sheet->setCellValue("K$rowNumber", $summary['total']);
    $rowNumber++;
}

$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);
$sheet->getColumnDimension('C')->setAutoSize(true);
$sheet->getColumnDimension('D')->setAutoSize(true);
$sheet->getColumnDimension('E')->setAutoSize(true);
$sheet->getColumnDimension('F')->setAutoSize(true);
$sheet->getColumnDimension('G')->setAutoSize(true);
$sheet->getColumnDimension('I')->setAutoSize(true);
$sheet->getColumnDimension('J')->setAutoSize(true);
$sheet->getColumnDimension('K')->setAutoSize(true);

// Apply borders to all used cells (including column H)
$highestColumn = $sheet->getHighestColumn(); // Get the highest column used
$highestRow = $sheet->getHighestRow(); // Get the highest row used

$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
$cellRange = "A1:" . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColumnIndex) . $highestRow;

// Apply borders to all used cells
$sheet->getStyle($cellRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);


$sheet->getStyle("H1:H$highestRow")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);



// Output the spreadsheet as an Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="sales_' . $month . '_' . $year . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
