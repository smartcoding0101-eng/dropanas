<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\Inventory\KardexService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductBulkImportService
{
    public function __construct(
        protected KardexService $kardexService
    ) {}

    /**
     * Construye el libro de trabajo con las 2 hojas: plantilla de productos y guía de megabodegas/categorías.
     */
    public function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // -------------------------------------------------------------
        // HOJA 1: Plantilla de Productos
        // -------------------------------------------------------------
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla Productos Bolidrop');

        $headers = [
            'A1' => 'SKU / Código Proveedor (*)',
            'B1' => 'Nombre del Producto (*)',
            'C1' => 'Categoría (*)',
            'D1' => 'Costo Mayorista BOB (*)',
            'E1' => 'Piso Mínimo BOB (*)',
            'F1' => 'Precio Sugerido BOB (*)',
            'G1' => 'Peso en Kg (*)',
            'H1' => 'Código Megabodega (*)',
            'I1' => 'Stock Inicial (Uds)',
            'J1' => 'Habilitado COD (SI/NO)',
            'K1' => 'Descripción / Especificaciones',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Estilos para el encabezado (Azul Pizarra Corporativo)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
                'name' => 'Segoe UI',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'], // Slate 900
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '334155'],
                ],
            ],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Filas de ejemplo realistas de Bolivia
        $examples = [
            [
                'sku'             => 'IMPR-EPSON-L4150',
                'name'            => 'Impresora Multifuncional Epson EcoTank L4150',
                'category'        => 'Tecnología y Gadgets',
                'cost'            => 120.00,
                'min'             => 140.00,
                'suggested'       => 199.00,
                'weight'          => 4.500,
                'warehouse'       => 'WH-SCZ-01',
                'stock'           => 50,
                'cod'             => 'SI',
                'description'     => 'Impresora con sistema continuo original para oficina y hogar. Alta rentabilidad.',
            ],
            [
                'sku'             => 'AUD-PRO-X9',
                'name'            => 'Auriculares Inalámbricos Pro Noise Cancelling',
                'category'        => 'Tecnología y Gadgets',
                'cost'            => 45.00,
                'min'             => 65.00,
                'suggested'       => 95.00,
                'weight'          => 0.350,
                'warehouse'       => 'WH-LPZ-01',
                'stock'           => 100,
                'cod'             => 'SI',
                'description'     => 'Auriculares bluetooth 5.3 con cancelación de ruido y estuche de carga USB-C.',
            ],
            [
                'sku'             => 'SET-SARTEN-GRAN',
                'name'            => 'Juego de Sartenes Antiadherentes Granito 3 Pzas',
                'category'        => 'Hogar y Cocina',
                'cost'            => 85.00,
                'min'             => 110.00,
                'suggested'       => 150.00,
                'weight'          => 2.100,
                'warehouse'       => 'WH-CBB-01',
                'stock'           => 30,
                'cod'             => 'SI',
                'description'     => 'Juego de 3 sartenes antiadherentes con mango atérmico de baquelita.',
            ],
        ];

        $rowIdx = 2;
        foreach ($examples as $item) {
            $sheet->setCellValueExplicit("A{$rowIdx}", $item['sku'], DataType::TYPE_STRING);
            $sheet->setCellValue("B{$rowIdx}", $item['name']);
            $sheet->setCellValue("C{$rowIdx}", $item['category']);
            $sheet->setCellValue("D{$rowIdx}", $item['cost']);
            $sheet->setCellValue("E{$rowIdx}", $item['min']);
            $sheet->setCellValue("F{$rowIdx}", $item['suggested']);
            $sheet->setCellValue("G{$rowIdx}", $item['weight']);
            $sheet->setCellValueExplicit("H{$rowIdx}", $item['warehouse'], DataType::TYPE_STRING);
            $sheet->setCellValue("I{$rowIdx}", $item['stock']);
            $sheet->setCellValue("J{$rowIdx}", $item['cod']);
            $sheet->setCellValue("K{$rowIdx}", $item['description']);

            // Formato de moneda y números
            $sheet->getStyle("D{$rowIdx}:F{$rowIdx}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("G{$rowIdx}")->getNumberFormat()->setFormatCode('0.000');
            $sheet->getStyle("I{$rowIdx}")->getNumberFormat()->setFormatCode('#,##0');

            // Alineaciones
            $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("H{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getRowDimension($rowIdx)->setRowHeight(22);
            $rowIdx++;
        }

        // Auto-ajustar ancho de columnas
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // -------------------------------------------------------------
        // HOJA 2: Guía de Códigos de Megabodegas y Categorías
        // -------------------------------------------------------------
        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Bodegas y Categorias Validas');

        $guideSheet->setCellValue('A1', 'MEGABODEGAS OFICIALES DE BOLIVIA');
        $guideSheet->mergeCells('A1:C1');
        $guideSheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);

        $guideSheet->setCellValue('A2', 'Código a usar');
        $guideSheet->setCellValue('B2', 'Nombre de la Bodega');
        $guideSheet->setCellValue('C2', 'Departamento / Ciudad');
        $guideSheet->getStyle('A2:C2')->applyFromArray($headerStyle);

        $warehouses = Warehouse::where('is_active', true)->get();
        $wRow = 3;
        foreach ($warehouses as $wh) {
            $guideSheet->setCellValueExplicit("A{$wRow}", $wh->code, DataType::TYPE_STRING);
            $guideSheet->setCellValue("B{$wRow}", $wh->name);
            $guideSheet->setCellValue("C{$wRow}", "{$wh->department} - {$wh->city}");
            $wRow++;
        }

        $cTitleRow = $wRow + 2;
        $guideSheet->setCellValue("A{$cTitleRow}", 'CATEGORÍAS ACTIVAS');
        $guideSheet->mergeCells("A{$cTitleRow}:B{$cTitleRow}");
        $guideSheet->getStyle("A{$cTitleRow}")->getFont()->setBold(true)->setSize(11);

        $cHeaderRow = $cTitleRow + 1;
        $guideSheet->setCellValue("A{$cHeaderRow}", 'Nombre de Categoría');
        $guideSheet->setCellValue("B{$cHeaderRow}", 'Slug / Identificador');
        $guideSheet->getStyle("A{$cHeaderRow}:B{$cHeaderRow}")->applyFromArray($headerStyle);

        $categories = Category::all();
        $cRow = $cHeaderRow + 1;
        foreach ($categories as $cat) {
            $guideSheet->setCellValue("A{$cRow}", $cat->name);
            $guideSheet->setCellValue("B{$cRow}", $cat->slug);
            $cRow++;
        }

        foreach (range('A', 'C') as $col) {
            $guideSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Escribe la plantilla directamente en la salida de streaming HTTP (php://output).
     */
    public function streamTemplate(string $format = 'xlsx'): void
    {
        $spreadsheet = $this->buildTemplateSpreadsheet();

        if ($format === 'csv') {
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $writer->setDelimiter(';');
            $writer->setEnclosure('"');
            $writer->save('php://output');
        } else {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }
    }

    /**
     * Genera la plantilla oficial y la guarda en un archivo temporal.
     */
    public function generateTemplate(string $format = 'xlsx'): array
    {
        $spreadsheet = $this->buildTemplateSpreadsheet();

        $fileName = 'plantilla_carga_masiva_productos_' . date('Ymd_His') . '.' . ($format === 'csv' ? 'csv' : 'xlsx');
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

        if ($format === 'csv') {
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $writer->setDelimiter(';');
            $writer->setEnclosure('"');
            $writer->save($tempPath);
            $mimeType = 'text/csv';
        } else {
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);
            $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        return [
            'path'     => $tempPath,
            'filename' => $fileName,
            'mime'     => $mimeType,
        ];
    }

    /**
     * Procesa la carga masiva desde un archivo Excel (.xlsx, .xls) o CSV (.csv).
     */
    public function processBulkUpload(UploadedFile $file, User $user): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = [];

        if ($extension === 'csv' || $file->getMimeType() === 'text/csv' || $file->getMimeType() === 'text/plain') {
            $rows = $this->parseCsv($file->getRealPath());
        } else {
            $rows = $this->parseSpreadsheet($file->getRealPath());
        }

        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'El archivo está vacío o no contiene filas de datos válidas.',
                'summary' => [
                    'total'   => 0,
                    'created' => 0,
                    'updated' => 0,
                    'failed'  => 0,
                ],
                'products' => [],
                'errors'   => ['No se encontraron registros para procesar.'],
            ];
        }

        // Cargar mapas en memoria para optimizar consultas
        $categoriesByName = Category::all()->keyBy(fn($c) => Str::slug($c->name));
        $categoriesBySlug = Category::all()->keyBy('slug');
        $defaultCategory  = Category::first();

        $warehousesByCode = Warehouse::where('is_active', true)->get()->keyBy(fn($w) => strtoupper(trim($w->code)));
        $warehousesByDept = Warehouse::where('is_active', true)->get()->keyBy(fn($w) => Str::slug($w->department));
        $defaultWarehouse = Warehouse::where('is_active', true)->first();

        $tenantId = $user->tenant_id ?? Tenant::value('id');

        // Determinar proveedor asignado
        $providerId = $user->id;
        if (!$user->isProvider()) {
            $providerId = User::where('role', 'provider')->value('id') ?? $user->id;
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $processedProducts = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +1 encabezado, +1 base 1
                $sku = trim((string) ($row['sku'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));

                if (empty($sku) || empty($name)) {
                    $errors[] = "Fila #{$rowNumber}: El SKU y el Nombre del Producto son obligatorios.";
                    continue;
                }

                $costPrice = (float) ($row['cost'] ?? 0);
                $minPrice  = (float) ($row['min'] ?? $costPrice);
                $sugPrice  = (float) ($row['suggested'] ?? $minPrice);

                if ($costPrice <= 0) {
                    $errors[] = "Fila #{$rowNumber} (SKU: {$sku}): El Costo Mayorista (BOB) debe ser mayor a 0.";
                    continue;
                }

                if ($minPrice < $costPrice) {
                    $minPrice = $costPrice;
                }

                if ($sugPrice < $minPrice) {
                    $sugPrice = $minPrice;
                }

                $weightKg = (float) ($row['weight'] ?? 0.5);
                if ($weightKg <= 0) {
                    $weightKg = 0.5;
                }

                // Resolver categoría
                $catRaw = trim((string) ($row['category'] ?? ''));
                $catSlug = Str::slug($catRaw);
                $category = $categoriesBySlug->get($catSlug)
                    ?? $categoriesByName->get($catSlug)
                    ?? $defaultCategory;

                if (!$category) {
                    $category = Category::create([
                        'tenant_id' => $tenantId,
                        'name'      => !empty($catRaw) ? $catRaw : 'General',
                        'slug'      => !empty($catSlug) ? $catSlug : 'general-' . rand(100, 999),
                    ]);
                    $categoriesByName->put(Str::slug($category->name), $category);
                }

                // Resolver bodega de destino
                $whRaw = strtoupper(trim((string) ($row['warehouse'] ?? '')));
                $warehouse = $warehousesByCode->get($whRaw)
                    ?? $warehousesByDept->get(Str::slug($whRaw))
                    ?? $defaultWarehouse;

                // Resolver COD
                $codRaw = strtolower(trim((string) ($row['cod'] ?? 'si')));
                $isCodAvailable = in_array($codRaw, ['si', 's', 'true', '1', 'yes', 'cod']);

                $stockInitial = max(0, (int) ($row['stock'] ?? 0));
                $description  = trim((string) ($row['description'] ?? ''));

                // Verificar si el producto ya existe (mismo SKU para este proveedor)
                $existingProduct = Product::withoutGlobalScopes()
                    ->where('provider_id', $providerId)
                    ->where('sku', $sku)
                    ->first();

                if ($existingProduct) {
                    // Actualizar producto existente conservando su Código Interno
                    $existingProduct->update([
                        'name'                => $name,
                        'category_id'         => $category->id,
                        'cost_price_bob'      => $costPrice,
                        'min_price_bob'       => $minPrice,
                        'suggested_price_bob' => $sugPrice,
                        'weight_kg'           => $weightKg,
                        'is_cod_available'    => $isCodAvailable,
                        'description'         => !empty($description) ? $description : $existingProduct->description,
                        'status'              => 'published',
                    ]);

                    $product = $existingProduct;
                    $action = 'actualizado';
                    $updated++;
                } else {
                    // Crear nuevo producto con Código Interno oficial de Bolidrop
                    $internalCode = Product::generateNextInternalCode();
                    $slug = Str::slug($name) . '-' . rand(1000, 9999);

                    $product = Product::create([
                        'tenant_id'           => $tenantId,
                        'provider_id'         => $providerId,
                        'category_id'         => $category->id,
                        'name'                => $name,
                        'slug'                => $slug,
                        'sku'                 => $sku,
                        'internal_code'       => $internalCode,
                        'description'         => $description,
                        'cost_price_bob'      => $costPrice,
                        'min_price_bob'       => $minPrice,
                        'suggested_price_bob' => $sugPrice,
                        'weight_kg'           => $weightKg,
                        'status'              => 'published',
                        'is_cod_available'    => $isCodAvailable,
                    ]);

                    $action = 'creado';
                    $created++;
                }

                // Asignar Inventario Físico y Registrar Kardex si corresponde
                if ($warehouse && $stockInitial > 0) {
                    $inv = WarehouseInventory::withoutGlobalScopes()->where([
                        'warehouse_id' => $warehouse->id,
                        'product_id'   => $product->id,
                    ])->first();

                    if ($inv) {
                        $inv->stock_physical += $stockInitial;
                        $inv->stock_available += $stockInitial;
                        $inv->save();
                    } else {
                        $inv = WarehouseInventory::create([
                            'tenant_id'       => $tenantId,
                            'warehouse_id'    => $warehouse->id,
                            'product_id'      => $product->id,
                            'stock_physical'  => $stockInitial,
                            'stock_reserved'  => 0,
                            'stock_available' => $stockInitial,
                        ]);
                    }

                    $this->kardexService->recordOpeningBalance(
                        $inv,
                        $user->id,
                        "Carga Masiva Excel - Ingreso de {$stockInitial} uds (SKU: {$product->sku} | Código: {$product->internal_code}) en Bodega {$warehouse->code}"
                    );
                }

                $processedProducts[] = [
                    'sku'            => $product->sku,
                    'internal_code'  => $product->internal_code,
                    'name'           => $product->name,
                    'category'       => $category->name,
                    'cost_bob'       => $product->cost_price_bob,
                    'suggested_bob'  => $product->suggested_price_bob,
                    'warehouse'      => $warehouse ? $warehouse->name : 'No asignada',
                    'stock'          => $stockInitial,
                    'action'         => $action,
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Proceso completado: {$created} productos creados, {$updated} actualizados.",
                'summary' => [
                    'total'   => count($rows),
                    'created' => $created,
                    'updated' => $updated,
                    'failed'  => count($errors),
                ],
                'products' => $processedProducts,
                'errors'   => $errors,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Ocurrió un error al procesar el archivo: ' . $e->getMessage(),
                'summary' => [
                    'total'   => count($rows),
                    'created' => 0,
                    'updated' => 0,
                    'failed'  => count($rows),
                ],
                'products' => [],
                'errors'   => [$e->getMessage()],
            ];
        }
    }

    /**
     * Parsea un archivo de hoja de cálculo (.xlsx / .xls).
     */
    protected function parseSpreadsheet(string $filePath): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $sheetData = $sheet->toArray(null, true, true, true);

        if (count($sheetData) < 2) {
            return [];
        }

        $headerRow = array_shift($sheetData);
        $colMap = $this->mapColumns($headerRow);

        $parsed = [];
        foreach ($sheetData as $row) {
            // Verificar si la fila está completamente vacía
            $allEmpty = true;
            foreach ($row as $val) {
                if (trim((string) $val) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                continue;
            }

            $item = $this->extractRowData($row, $colMap);
            if (!empty($item['sku']) || !empty($item['name'])) {
                $parsed[] = $item;
            }
        }

        return $parsed;
    }

    /**
     * Parsea un archivo CSV detectando delimitadores y BOM.
     */
    protected function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        // Leer primera línea para detectar delimitador
        $firstLine = fgets($handle);
        if (!$firstLine) {
            fclose($handle);
            return [];
        }

        // Eliminar UTF-8 BOM si existe
        if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            $firstLine = substr($firstLine, 3);
        }

        $delimiter = ';';
        if (substr_count($firstLine, ',') > substr_count($firstLine, ';')) {
            $delimiter = ',';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ';')) {
            $delimiter = "\t";
        }

        rewind($handle);
        // Descartar BOM si existe
        $bomCheck = fread($handle, 3);
        if ($bomCheck !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headerRow = fgetcsv($handle, 0, $delimiter);
        if (!$headerRow) {
            fclose($handle);
            return [];
        }

        $colMap = $this->mapColumns($headerRow);
        $parsed = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $allEmpty = true;
            foreach ($row as $val) {
                if (trim((string) $val) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                continue;
            }

            $item = $this->extractRowData($row, $colMap);
            if (!empty($item['sku']) || !empty($item['name'])) {
                $parsed[] = $item;
            }
        }

        fclose($handle);
        return $parsed;
    }

    /**
     * Mapea encabezados a nombres de campos normalizados de forma tolerante.
     */
    protected function mapColumns(array $headers): array
    {
        $map = [];

        foreach ($headers as $colKey => $headerText) {
            $clean = Str::slug((string) $headerText);

            if (Str::contains($clean, ['bodega', 'almacen', 'warehouse', 'megabodega'])) {
                $map['warehouse'] = $colKey;
            } elseif (Str::contains($clean, ['sku', 'codigo-prov', 'codigo-unico', 'codigo-prod', 'codigo-art']) || $clean === 'codigo') {
                $map['sku'] = $colKey;
            } elseif (Str::contains($clean, ['nombre', 'producto', 'name', 'titulo'])) {
                $map['name'] = $colKey;
            } elseif (Str::contains($clean, ['categoria', 'category'])) {
                $map['category'] = $colKey;
            } elseif (Str::contains($clean, ['costo', 'cost', 'precio-costo'])) {
                $map['cost'] = $colKey;
            } elseif (Str::contains($clean, ['piso', 'minimo', 'precio-min'])) {
                $map['min'] = $colKey;
            } elseif (Str::contains($clean, ['sugerido', 'venta', 'suggested'])) {
                $map['suggested'] = $colKey;
            } elseif (Str::contains($clean, ['peso', 'weight', 'kg'])) {
                $map['weight'] = $colKey;
            } elseif (Str::contains($clean, ['stock', 'cantidad', 'unidades', 'cant'])) {
                $map['stock'] = $colKey;
            } elseif (Str::contains($clean, ['cod', 'contra-entrega', 'efectivo'])) {
                $map['cod'] = $colKey;
            } elseif (Str::contains($clean, ['descripcion', 'desc', 'detalle'])) {
                $map['description'] = $colKey;
            }
        }

        // Mapeo posicional por defecto si no se detectaron encabezados exactos
        $keys = array_keys($headers);
        if (!isset($map['sku']) && isset($keys[0])) $map['sku'] = $keys[0];
        if (!isset($map['name']) && isset($keys[1])) $map['name'] = $keys[1];
        if (!isset($map['category']) && isset($keys[2])) $map['category'] = $keys[2];
        if (!isset($map['cost']) && isset($keys[3])) $map['cost'] = $keys[3];
        if (!isset($map['min']) && isset($keys[4])) $map['min'] = $keys[4];
        if (!isset($map['suggested']) && isset($keys[5])) $map['suggested'] = $keys[5];
        if (!isset($map['weight']) && isset($keys[6])) $map['weight'] = $keys[6];
        if (!isset($map['warehouse']) && isset($keys[7])) $map['warehouse'] = $keys[7];
        if (!isset($map['stock']) && isset($keys[8])) $map['stock'] = $keys[8];
        if (!isset($map['cod']) && isset($keys[9])) $map['cod'] = $keys[9];
        if (!isset($map['description']) && isset($keys[10])) $map['description'] = $keys[10];

        return $map;
    }

    /**
     * Extrae los valores limpios de una fila usando el mapa de columnas.
     */
    protected function extractRowData(array $row, array $colMap): array
    {
        $get = fn($key) => isset($colMap[$key], $row[$colMap[$key]]) ? trim((string) $row[$colMap[$key]]) : '';

        $cleanNumber = function(string $val): float {
            $clean = str_replace(['Bs', 'bs', '$', ' ', "\t"], '', $val);
            if (str_contains($clean, ',') && str_contains($clean, '.')) {
                $clean = str_replace(',', '', $clean);
            } elseif (str_contains($clean, ',')) {
                $clean = str_replace(',', '.', $clean);
            }
            return (float) $clean;
        };

        return [
            'sku'         => $get('sku'),
            'name'        => $get('name'),
            'category'    => $get('category'),
            'cost'        => $cleanNumber($get('cost')),
            'min'         => $cleanNumber($get('min')),
            'suggested'   => $cleanNumber($get('suggested')),
            'weight'      => $cleanNumber($get('weight')),
            'warehouse'   => $get('warehouse'),
            'stock'       => (int) $cleanNumber($get('stock')),
            'cod'         => $get('cod'),
            'description' => $get('description'),
        ];
    }
}
