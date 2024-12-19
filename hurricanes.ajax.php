<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'helpers/db.php';

$db = DB::instance('localhost', 'root', '', 'runni');

// Check the action
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'getColumns':
            $columns = $db->getColumns('runni', 'hurricanes');
            echo json_encode($columns);
            exit;
            break;
        case 'getDetails':
            $sid = $_POST['sid'] ?? '';
            if ($sid === '') {
                echo json_encode([]);
                exit;
            }

            // Fetch all records for this SID
            $stmt = $db->pdo->prepare("SELECT * FROM hurricanes WHERE SID = :sid ORDER BY ISO_TIME");
            $stmt->execute([':sid' => $sid]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($records);
            exit;
            break;
    }
}

// If we reach here, it's a normal DataTables request for main table

$draw   = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start  = isset($_POST['start']) ? (int)$_POST['start'] : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
$searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$columnsRequest = $_POST['columns'] ?? [];
$orderRequest = $_POST['order'] ?? [];

// Extract column names from DataTables request
$columns = array_map(function ($col) {
    return $col['data'];
}, $columnsRequest);

// Build order clause for main table
$orderByClause = '';
if (is_array($orderRequest) && !empty($orderRequest)) {
    $orderParts = [];
    foreach ($orderRequest as $orderReq) {
        $colIndex = (int)$orderReq['column'];
        $dir = $orderReq['dir'] === 'asc' ? 'ASC' : 'DESC';
        if (isset($columns[$colIndex])) {
            $colName = $columns[$colIndex];
            if (preg_match('/^[A-Za-z0-9_]+$/', $colName)) {
                $orderParts[] = "$colName $dir";
            }
        }
    }
    if (!empty($orderParts)) {
        $orderByClause = implode(', ', $orderParts);
    }
}

// Build where clause for searching
$whereClause = '';
$whereParams = [];
if ($searchValue !== '') {
    $likeClauses = [];
    foreach ($columns as $colName) {
        if (preg_match('/^[A-Za-z0-9_]+$/', $colName)) {
            $likeClauses[] = "$colName LIKE :search";
        }
    }
    if (!empty($likeClauses)) {
        $whereClause = '(' . implode(' OR ', $likeClauses) . ')';
        $whereParams[':search'] = '%' . $searchValue . '%';
    }
}

// Distinct SIDs total
$stmtTotal = $db->pdo->query("SELECT COUNT(DISTINCT SID) FROM hurricanes");
$totalRecords = (int)$stmtTotal->fetchColumn();

// mainRecordSQL: subquery to find one main record per SID
// If LANDFALL=0 exists, pick earliest ISO_TIME among LANDFALL=0 records
// else pick latest ISO_TIME
// First, apply filters in a subselect
$filterConditions = '';
if ($whereClause !== '') {
    $filterConditions = "WHERE $whereClause";
}

$mainRecordSQL = "
    SELECT SID,
        CASE 
            WHEN SUM(CASE WHEN LANDFALL=0 THEN 1 ELSE 0 END) > 0 
            THEN (
                SELECT MIN(ISO_TIME)
                FROM hurricanes h2
                WHERE h2.SID = h.SID
                " . ($whereClause !== '' ? "AND $whereClause" : "") . "
                AND LANDFALL=0
            )
            ELSE (
                SELECT MAX(ISO_TIME)
                FROM hurricanes h3
                WHERE h3.SID = h.SID
                " . ($whereClause !== '' ? "AND $whereClause" : "") . "
            )
        END AS main_time
    FROM hurricanes h
    $filterConditions
    GROUP BY SID
";

// Count how many SIDs matched after filtering
$stmtCount = $db->pdo->prepare("SELECT COUNT(*) FROM ($mainRecordSQL) AS temp");
$stmtCount->execute($whereParams);
$filteredCount = (int)$stmtCount->fetchColumn();

// Build final query to get main records
$finalSQL = "
    SELECT main_table.*
    FROM hurricanes main_table
    INNER JOIN (
        $mainRecordSQL
    ) main_sel ON main_table.SID = main_sel.SID AND main_table.ISO_TIME = main_sel.main_time
";

if ($orderByClause !== '') {
    $finalSQL .= " ORDER BY $orderByClause";
}

$finalSQL .= " LIMIT $start, $length";

$stmt = $db->pdo->prepare($finalSQL);
$stmt->execute($whereParams);
$resultPage = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return just these main records, no ALL_RECORDS_JSON
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $filteredCount,
    'data' => $resultPage
]);
