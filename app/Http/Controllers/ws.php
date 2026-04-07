<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
date_default_timezone_set("Asia/Jakarta");

require __DIR__ . "/config/DbClass.php";
require __DIR__ . "/config/conn.php";
require __DIR__ . "/config/jwt.php";

$isExport = false;
$rawInput = file_get_contents("php://input");
$tmpInput = json_decode($rawInput, true);
if (is_array($tmpInput) && ($tmpInput["method"] ?? "") === "exportSiswa") {
    $isExport = true;
}
if (!$isExport && !empty($_POST["method"]) && $_POST["method"] === "exportSiswa") {
    $isExport = true;
}
if (!$isExport && !empty($_GET["method"]) && $_GET["method"] === "exportSiswa") {
    $isExport = true;
}

if (!$isExport) {
    header("Content-Type: application/json; charset=utf-8");
}
header("Access-Control-Allow-Origin: http://localhost:8000");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
    http_response_code(204);
    exit;
}

function writeLog($data): void
{
    $line = "[" . date("Y-m-d H:i:s") . "] ";
    $line .= is_array($data) || is_object($data)
        ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        : (string) $data;

    file_put_contents(__DIR__ . "/error.log", $line . PHP_EOL, FILE_APPEND);
}

/** Log khusus performa getDataPenerimaan (satu baris JSON per request). */
function penerimaanPerfLog(array $entry): void
{
    $entry["at"] = date("Y-m-d H:i:s");
    $path = __DIR__ . "/penerimaan_perf.log";
    $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        http_response_code(500);
        echo json_encode([
            "status" => 500,
            "message" => "ENV tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === "" || str_starts_with($line, "#")) {
            continue;
        }

        if (!str_contains($line, "=")) {
            continue;
        }

        [$name, $value] = explode("=", $line, 2);
        $name = trim($name);
        $value = trim(trim($value), "\"'");

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function getJsonInput(): array
{
    $raw = file_get_contents("php://input");
    $json = json_decode($raw, true);

    if (is_array($json)) {
        return $json;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    if (!empty($_GET)) {
        return $_GET;
    }

    return [];
}

function dbConnectPdo(): PDO
{
    $host = (string) ($_ENV["DB_HOST"] ?? "");
    $user = (string) ($_ENV["DB_USERNAME"] ?? "");
    $pass = (string) ($_ENV["DB_PASSWORD"] ?? "");
    $port = (string) ($_ENV["DB_PORT"] ?? "3306");
    $name = (string) ($_ENV["DB_DATABASE"] ?? "");

    if ($host === "" || $user === "" || $name === "") {
        throw new RuntimeException("ENV_DB_INCOMPLETE");
    }

    $conn = new conn();
    $pdo = $conn->DBConnect([
        "host" => $host,
        "user" => $user,
        "pass" => $pass,
        "port" => $port,
        "name" => $name,
    ]);

    if (!$pdo instanceof PDO) {
        throw new RuntimeException("DBConnect tidak mengembalikan PDO");
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

function getDashboard(): array
{
    $pdo = dbConnectPdo();

    $sql = "
        SELECT
            TRIM(b.BILLNM) AS billname,
            b.BILLAM AS billam,
            b.PAIDDT AS paiddt,
            TRIM(c.NMCUST) AS nama_cust,
            TRIM(c.CODE02) AS unit,
            TRIM(c.DESC02) AS desc02,
            TRIM(c.DESC03) AS desc03,
            TRIM(c.DESC04) AS desc04,
            CONCAT(
                TRIM(COALESCE(c.DESC03, '')),
                ' - ',
                TRIM(COALESCE(c.DESC04, ''))
            ) AS angkatan
        FROM scctbill b
        INNER JOIN scctcust c
            ON c.CUSTID = b.CUSTID
        WHERE b.PAIDST = '1'
          AND b.FSTSBolehBayar = 1
          AND b.PAIDDT IS NOT NULL
        ORDER BY b.PAIDDT DESC
        LIMIT 5
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getTagihanDashboard(): array
{
    $pdo = dbConnectPdo();

    $sql = "
        SELECT
            COUNT(DISTINCT CONCAT(CUSTID, '|', BILLCD)) AS jumlah_tagihan,
            COUNT(DISTINCT CASE
                WHEN PAIDST = '1' AND FSTSBolehBayar = 1
                THEN CONCAT(CUSTID, '|', BILLCD)
            END) AS tagihan_dibayar,
            COUNT(DISTINCT CASE
                WHEN PAIDST = '0' AND FSTSBolehBayar = 1
                THEN CONCAT(CUSTID, '|', BILLCD)
            END) AS tagihan_belum_dibayar
        FROM scctbill
        WHERE FSTSBolehBayar = 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch();

    return [
        "jumlah_tagihan" => (int) ($row["jumlah_tagihan"] ?? 0),
        "tagihan_dibayar" => (int) ($row["tagihan_dibayar"] ?? 0),
        "tagihan_belum_dibayar" => (int) ($row["tagihan_belum_dibayar"] ?? 0)
    ];
}

function getTagihanBayarDashboard(): array
{
    $pdo = dbConnectPdo();

    $endDate = new DateTime("today");
    $startDate = (clone $endDate)->modify("-4 days");

    $sql = "
        SELECT
            DATE(b.PAIDDT) AS tanggal,
            COUNT(DISTINCT CONCAT(b.CUSTID, '|', b.BILLCD)) AS total
        FROM scctbill b
        WHERE b.PAIDST = '1'
          AND b.FSTSBolehBayar = 1
          AND b.PAIDDT IS NOT NULL
          AND DATE(b.PAIDDT) BETWEEN :start_date AND :end_date
        GROUP BY DATE(b.PAIDDT)
        ORDER BY DATE(b.PAIDDT) ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":start_date", $startDate->format("Y-m-d"), PDO::PARAM_STR);
    $stmt->bindValue(":end_date", $endDate->format("Y-m-d"), PDO::PARAM_STR);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    $map = [];
    foreach ($rows as $row) {
        $map[$row["tanggal"]] = (int) $row["total"];
    }

    $data = [];
    $period = clone $startDate;

    while ($period <= $endDate) {
        $ymd = $period->format("Y-m-d");

        $data[] = [
            "tanggal" => $ymd,
            "label" => $period->format("d M"),
            "total" => $map[$ymd] ?? 0
        ];

        $period->modify("+1 day");
    }

    return $data;
}

function getKelas(array $req): array
{
    $pdo = dbConnectPdo();

    $where = [];
    $params = [];

    if (!empty($req["jenjang"])) {
        $where[] = "jenjang = :jenjang";
        $params[":jenjang"] = $req["jenjang"];
    }

    if (!empty($req["unit"])) {
        $where[] = "unit = :unit";
        $params[":unit"] = $req["unit"];
    }

    if (!empty($req["kelompok"])) {
        $where[] = "kelompok = :kelompok";
        $params[":kelompok"] = $req["kelompok"];
    }

    $sql = "SELECT id, kelas, jenjang, unit, kelompok FROM mst_kelas";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY jenjang, kelas ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getKelasByid(array $req): array
{
    $id = (int) ($req["id"] ?? 0);

    if ($id <= 0) {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "ID tidak valid"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $stmt = $pdo->prepare("SELECT id, kelas, jenjang, unit, kelompok FROM mst_kelas WHERE id = :id");
    $stmt->execute([":id" => $id]);

    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            "status" => 404,
            "message" => "Data kelas tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $row;
}

function createKelas(array $req): array
{
    $kelas    = trim((string) ($req["kelas"] ?? ""));
    $jenjang  = trim((string) ($req["jenjang"] ?? ""));
    $unit     = trim((string) ($req["unit"] ?? ""));
    $kelompok = trim((string) ($req["kelompok"] ?? ""));

    if ($kelas === "" || $jenjang === "" || $unit === "" || $kelompok === "") {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "Field kelas, jenjang, unit, kelompok wajib diisi"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $check = $pdo->prepare("SELECT id FROM mst_kelas WHERE kelas = :kelas AND unit = :unit");
    $check->execute([":kelas" => $kelas, ":unit" => $unit]);

    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode([
            "status" => 409,
            "message" => "Kelas sudah ada pada unit tersebut"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO mst_kelas (kelas, jenjang, unit, kelompok)
        VALUES (:kelas, :jenjang, :unit, :kelompok)
    ");

    $stmt->execute([
        ":kelas"    => $kelas,
        ":jenjang"  => $jenjang,
        ":unit"     => $unit,
        ":kelompok" => $kelompok,
    ]);

    $newId = (int) $pdo->lastInsertId();

    return [
        "id"       => $newId,
        "kelas"    => $kelas,
        "jenjang"  => $jenjang,
        "unit"     => $unit,
        "kelompok" => $kelompok,
    ];
}

function deleteKelas(array $req): array
{
    $id = (int) ($req["id"] ?? 0);

    if ($id <= 0) {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "ID tidak valid"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $exist = $pdo->prepare("SELECT id, kelas FROM mst_kelas WHERE id = :id");
    $exist->execute([":id" => $id]);
    $row = $exist->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            "status" => 404,
            "message" => "Data kelas tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM mst_kelas WHERE id = :id");
    $stmt->execute([":id" => $id]);

    return [
        "id"    => $id,
        "kelas" => $row["kelas"],
    ];
}

function getAkun(array $req): array
{
    $pdo = dbConnectPdo();

    $where = [];
    $params = [];

    if (!empty($req["NamaAkun"])) {
        $where[] = "NamaAkun LIKE :NamaAkun";
        $params[":NamaAkun"] = "%" . $req["NamaAkun"] . "%";
    }

    $sql = "SELECT KodeAkun, NamaAkun, NoRek FROM u_akun";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY KodeAkun ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getAkunByKode(array $req): array
{
    $kode = trim((string) ($req["KodeAkun"] ?? ""));

    if ($kode === "") {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "KodeAkun wajib diisi"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $stmt = $pdo->prepare("SELECT KodeAkun, NamaAkun, NoRek FROM u_akun WHERE KodeAkun = :KodeAkun");
    $stmt->execute([":KodeAkun" => $kode]);

    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            "status" => 404,
            "message" => "Data akun tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $row;
}

function createAkun(array $req): array
{
    $kode  = trim((string) ($req["KodeAkun"] ?? ""));
    $nama  = trim((string) ($req["NamaAkun"] ?? ""));
    $norek = trim((string) ($req["NoRek"] ?? ""));

    if ($kode === "" || $nama === "") {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "Field KodeAkun dan NamaAkun wajib diisi"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (strlen($kode) > 5) {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "KodeAkun maksimal 5 karakter"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $check = $pdo->prepare("SELECT KodeAkun FROM u_akun WHERE KodeAkun = :KodeAkun");
    $check->execute([":KodeAkun" => $kode]);

    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode([
            "status" => 409,
            "message" => "KodeAkun sudah terdaftar"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO u_akun (KodeAkun, NamaAkun, NoRek)
        VALUES (:KodeAkun, :NamaAkun, :NoRek)
    ");

    $stmt->execute([
        ":KodeAkun" => $kode,
        ":NamaAkun" => $nama,
        ":NoRek"    => $norek !== "" ? $norek : null,
    ]);

    return [
        "KodeAkun" => $kode,
        "NamaAkun" => $nama,
        "NoRek"    => $norek !== "" ? $norek : null,
    ];
}

function getSiswa(array $req): array
{
    $pdo = dbConnectPdo();

    $where = [];
    $params = [];

    if (!empty($req["search"])) {
        $q = "%" . trim($req["search"]) . "%";
        $where[] = "(TRIM(NMCUST) LIKE :search OR TRIM(NOCUST) LIKE :search2 OR TRIM(NUM2ND) LIKE :search3)";
        $params[":search"]  = $q;
        $params[":search2"] = $q;
        $params[":search3"] = $q;
    }

    if (!empty($req["DESC04"])) {
        $where[] = "TRIM(DESC04) = :DESC04";
        $params[":DESC04"] = trim($req["DESC04"]);
    }

    if (!empty($req["CODE02"])) {
        $where[] = "TRIM(CODE02) = :CODE02";
        $params[":CODE02"] = trim($req["CODE02"]);
    }

    if (!empty($req["DESC02"])) {
        $where[] = "TRIM(DESC02) = :DESC02";
        $params[":DESC02"] = trim($req["DESC02"]);
    }

    if (isset($req["STCUST"]) && $req["STCUST"] !== "") {
        $where[] = "STCUST = :STCUST";
        $params[":STCUST"] = trim($req["STCUST"]);
    }

    $sql = "
        SELECT
            CUSTID,
            TRIM(NOCUST) AS NOCUST,
            TRIM(NMCUST) AS NMCUST,
            TRIM(NUM2ND) AS NUM2ND,
            STCUST,
            TRIM(CODE01) AS CODE01,
            TRIM(DESC01) AS DESC01,
            TRIM(CODE02) AS CODE02,
            TRIM(DESC02) AS DESC02,
            TRIM(CODE03) AS CODE03,
            TRIM(DESC03) AS DESC03,
            TRIM(CODE04) AS CODE04,
            TRIM(DESC04) AS DESC04,
            TRIM(CODE05) AS CODE05,
            TRIM(DESC05) AS DESC05,
            TRIM(TOTPAY) AS TOTPAY,
            TRIM(GENUS) AS GENUS,
            LastUpdate
        FROM scctcust
    ";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY NMCUST ASC";

    $limit = (int) ($req["limit"] ?? 50);
    $offset = (int) ($req["offset"] ?? 0);

    if ($limit > 200) {
        $limit = 200;
    }

    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }

    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getFilterSiswa(): array
{
    $pdo = dbConnectPdo();

    $stmtAngkatan = $pdo->prepare("
        SELECT DISTINCT TRIM(DESC04) AS DESC04
        FROM scctcust
        WHERE DESC04 IS NOT NULL AND TRIM(DESC04) != ''
        ORDER BY DESC04 DESC
    ");
    $stmtAngkatan->execute();
    $angkatan = array_column($stmtAngkatan->fetchAll(), "DESC04");

    $stmtSekolah = $pdo->prepare("
        SELECT DISTINCT TRIM(CODE02) AS CODE02
        FROM scctcust
        WHERE CODE02 IS NOT NULL AND TRIM(CODE02) != ''
        ORDER BY CODE02 ASC
    ");
    $stmtSekolah->execute();
    $sekolah = array_column($stmtSekolah->fetchAll(), "CODE02");

    $stmtKelas = $pdo->prepare("
        SELECT DISTINCT TRIM(CODE02) AS CODE02, TRIM(DESC02) AS DESC02
        FROM scctcust
        WHERE DESC02 IS NOT NULL AND TRIM(DESC02) != ''
        ORDER BY CODE02 ASC, DESC02 ASC
    ");
    $stmtKelas->execute();
    $kelas = $stmtKelas->fetchAll();

    return [
        "angkatan" => $angkatan,
        "sekolah"  => $sekolah,
        "kelas"    => $kelas,
    ];
}

function getSiswaByCustid(array $req): array
{
    $custid = (int) ($req["CUSTID"] ?? 0);

    if ($custid <= 0) {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "CUSTID tidak valid"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $stmt = $pdo->prepare("
        SELECT
            CUSTID,
            TRIM(NOCUST) AS NOCUST,
            TRIM(NMCUST) AS NMCUST,
            TRIM(NUM2ND) AS NUM2ND,
            STCUST,
            TRIM(CODE01) AS CODE01,
            TRIM(DESC01) AS DESC01,
            TRIM(CODE02) AS CODE02,
            TRIM(DESC02) AS DESC02,
            TRIM(CODE03) AS CODE03,
            TRIM(DESC03) AS DESC03,
            TRIM(CODE04) AS CODE04,
            TRIM(DESC04) AS DESC04,
            TRIM(CODE05) AS CODE05,
            TRIM(DESC05) AS DESC05,
            TRIM(TOTPAY) AS TOTPAY,
            TRIM(GENUS) AS GENUS,
            LastUpdate
        FROM scctcust
        WHERE CUSTID = :CUSTID
    ");

    $stmt->execute([":CUSTID" => $custid]);

    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            "status" => 404,
            "message" => "Data siswa tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Saldo VA diambil dari view v_saldo_va (sesuai kebutuhan Manual Pembayaran).
    $saldoVa = 0;
    // Coba key CUSTID dulu; jika gagal, tetap lanjut fallback by NOCUST / NO_VA.
    try {
        $stmtSaldo = $pdo->prepare("
            SELECT CAST(COALESCE(SALDO, 0) AS SIGNED) AS SALDO
            FROM v_saldo_va
            WHERE CUSTID = :CUSTID
            LIMIT 1
        ");
        $stmtSaldo->execute([":CUSTID" => $custid]);
        $saldoRow = $stmtSaldo->fetch();
        if (is_array($saldoRow)) {
            $saldoVa = (int) ($saldoRow["SALDO"] ?? 0);
        }
    } catch (Throwable $e) {
        // abaikan: beberapa skema view tidak punya kolom CUSTID
    }

    if ($saldoVa === 0) {
        try {
            $nocustDigits = preg_replace('/\D+/', '', (string) ($row["NOCUST"] ?? ""));
            $noVa = "7510050" . ($nocustDigits !== "" ? $nocustDigits : "0");
            $stmtSaldo2 = $pdo->prepare("
                SELECT CAST(COALESCE(SALDO, 0) AS SIGNED) AS SALDO
                FROM v_saldo_va
                WHERE TRIM(CAST(NOCUST AS CHAR)) = TRIM(:nocust)
                   OR TRIM(CAST(NO_VA AS CHAR)) = TRIM(:nova)
                   OR TRIM(CAST(VA AS CHAR)) = TRIM(:nova)
                LIMIT 1
            ");
            $stmtSaldo2->execute([
                ":nocust" => trim((string) ($row["NOCUST"] ?? "")),
                ":nova" => $noVa,
            ]);
            $saldoRow2 = $stmtSaldo2->fetch();
            if (is_array($saldoRow2)) {
                $saldoVa = (int) ($saldoRow2["SALDO"] ?? 0);
            }
        } catch (Throwable $e2) {
            // view tidak punya kolom NOCUST/NO_VA — abaikan
        }
    }

    // Fallback terakhir: hitung saldo dari transaksi (KREDIT - DEBET).
    if ($saldoVa === 0) {
        try {
            $stmtTran = $pdo->prepare("
                SELECT
                    CAST(COALESCE(SUM(KREDIT), 0) AS SIGNED) - CAST(COALESCE(SUM(DEBET), 0) AS SIGNED) AS SALDO_NETTO
                FROM sccttran
                WHERE CUSTID = :custid
            ");
            $stmtTran->execute([":custid" => $custid]);
            $tranRow = $stmtTran->fetch();
            if (is_array($tranRow)) {
                $saldoVa = (int) ($tranRow["SALDO_NETTO"] ?? 0);
            }
        } catch (Throwable $e3) {
            // tabel/kolom transaksi tidak tersedia — biarkan saldo 0
        }
    }

    $thnAka = trim((string) ($req["thn_aka"] ?? ""));
    $tagihanWhere = "
        CUSTID = :CUSTID
          AND FSTSBolehBayar = 1
          AND (PAIDST = '0' OR PAIDST = 0 OR TRIM(CAST(PAIDST AS CHAR)) = '0')
    ";
    $tagihanParams = [":CUSTID" => $custid];
    if ($thnAka !== "") {
        $tagihanWhere .= "
          AND (
            UPPER(TRIM(BTA)) = UPPER(TRIM(:bta))
            OR UPPER(TRIM(BTA)) LIKE CONCAT(UPPER(TRIM(:bta_like)), '%')
            OR LEFT(TRIM(BTA), 4) = LEFT(TRIM(:bta_yr), 4)
          )
        ";
        $tagihanParams[":bta"] = $thnAka;
        $tagihanParams[":bta_like"] = $thnAka;
        $tagihanParams[":bta_yr"] = $thnAka;
    }

    // Tagihan yang ditampilkan pada Manual Pembayaran adalah yang belum lunas.
    $stmtTagihan = $pdo->prepare("
        SELECT
            CUSTID,
            TRIM(BILLCD) AS BILLCD,
            TRIM(BILLAC) AS BILLAC,
            TRIM(BILLNM) AS BILLNM,
            CAST(COALESCE(BILLAM, 0) AS SIGNED) AS BILLAM,
            TRIM(BTA) AS BTA,
            COALESCE(furutan, 0) AS furutan,
            DATE_FORMAT(FTGLTagihan, '%Y-%m-%d') AS FTGLTagihan
        FROM scctbill
        WHERE {$tagihanWhere}
        ORDER BY COALESCE(furutan, 0) ASC, FTGLTagihan DESC, BILLCD ASC
    ");
    $stmtTagihan->execute($tagihanParams);
    $tagihanBelumLunas = $stmtTagihan->fetchAll();

    $selectedBillcds = $req["selected_billcds"] ?? [];
    if (!is_array($selectedBillcds)) {
        $selectedBillcds = [];
    }
    $selectedMap = [];
    foreach ($selectedBillcds as $v) {
        $b = trim((string) $v);
        if ($b !== '') {
            $selectedMap[$b] = true;
        }
    }

    $totalTagihanBelumLunas = 0;
    $totalTagihanSelected = 0;
    $rowsTagihan = is_array($tagihanBelumLunas) ? $tagihanBelumLunas : [];
    foreach ($rowsTagihan as &$t) {
        $billcd = trim((string) ($t["BILLCD"] ?? ""));
        $billam = (int) ($t["BILLAM"] ?? 0);
        $isSelected = isset($selectedMap[$billcd]);
        $t["is_selected"] = $isSelected ? 1 : 0;
        $totalTagihanBelumLunas += $billam;
        if ($isSelected) {
            $totalTagihanSelected += $billam;
        }
    }
    unset($t);

    $row["SALDO_VA"] = $saldoVa;
    $row["tagihan_belum_lunas"] = $rowsTagihan;
    $row["TOTAL_TAGIHAN_BELUM_LUNAS"] = $totalTagihanBelumLunas;
    $row["TOTAL_TAGIHAN_SELECTED"] = $totalTagihanSelected;
    // Kompatibilitas field total_tagihan:
    // - jika ada selected_billcds, total_tagihan = total selected
    // - jika tidak ada selected_billcds, total_tagihan = 0 (menunggu user pilih)
    $row["TOTAL_TAGIHAN"] = count($selectedMap) > 0 ? $totalTagihanSelected : 0;

    return $row;
}

function getThnAka(): array
{
    $pdo = dbConnectPdo();

    $stmt = $pdo->prepare("SELECT urut, thn_aka FROM mst_thn_aka ORDER BY urut DESC");
    $stmt->execute();

    return $stmt->fetchAll();
}

function getThnAkaByUrut(array $req): array
{
    $urut = (int) ($req["urut"] ?? 0);

    if ($urut <= 0) {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "urut tidak valid"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $stmt = $pdo->prepare("SELECT urut, thn_aka FROM mst_thn_aka WHERE urut = :urut");
    $stmt->execute([":urut" => $urut]);

    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            "status" => 404,
            "message" => "Data tahun akademik tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $row;
}

function createThnAka(array $req): array
{
    $thn_aka = trim((string) ($req["thn_aka"] ?? ""));

    if ($thn_aka === "") {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "Field thn_aka wajib diisi"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $check = $pdo->prepare("SELECT urut FROM mst_thn_aka WHERE thn_aka = :thn_aka");
    $check->execute([":thn_aka" => $thn_aka]);

    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode([
            "status" => 409,
            "message" => "Tahun akademik sudah terdaftar"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO mst_thn_aka (thn_aka) VALUES (:thn_aka)");
    $stmt->execute([":thn_aka" => $thn_aka]);

    $newUrut = (int) $pdo->lastInsertId();

    return [
        "urut"    => $newUrut,
        "thn_aka" => $thn_aka,
    ];
}

function getBebanPost(array $req): array
{
    $pdo = dbConnectPdo();

    $where = [];
    $params = [];

    if (!empty($req["thn_masuk"])) {
        $where[] = "TRIM(d.thn_masuk) = :thn_masuk";
        $params[":thn_masuk"] = trim($req["thn_masuk"]);
    }

    if (!empty($req["kode_prod"])) {
        $where[] = "TRIM(d.kode_prod) = :kode_prod";
        $params[":kode_prod"] = trim($req["kode_prod"]);
    }

    if (!empty($req["KodeAkun"])) {
        $where[] = "TRIM(d.KodeAkun) = :KodeAkun";
        $params[":KodeAkun"] = trim($req["KodeAkun"]);
    }

    if (!empty($req["nominal"])) {
        $where[] = "TRIM(d.nominal) = :nominal";
        $params[":nominal"] = trim($req["nominal"]);
    }

    $sql = "
        SELECT
            d.urut,
            TRIM(d.kode_fak) AS kode_fak,
            TRIM(d.kode_prod) AS kode_prod,
            TRIM(d.KodeAkun) AS KodeAkun,
            TRIM(d.thn_masuk) AS thn_masuk,
            TRIM(d.nominal) AS nominal,
            TRIM(d.NamaAkun) AS NamaAkun,
            TRIM(d.NoRek) AS NoRek,
            TRIM(k.kelas) AS nama_kelas,
            TRIM(k.jenjang) AS jenjang,
            TRIM(k.unit) AS unit
        FROM u_daftar_harga d
        LEFT JOIN mst_kelas k ON k.id = d.kode_prod
    ";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY d.thn_masuk DESC, d.urut ASC";

    $limit = (int) ($req["limit"] ?? 50);
    $offset = (int) ($req["offset"] ?? 0);

    if ($limit > 200) {
        $limit = 200;
    }

    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }

    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getBebanPostByUrut(array $req): array
{
    $urut = (int) ($req["urut"] ?? 0);

    if ($urut <= 0) {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "urut tidak valid"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $stmt = $pdo->prepare("
        SELECT
            d.urut,
            TRIM(d.kode_fak) AS kode_fak,
            TRIM(d.kode_prod) AS kode_prod,
            TRIM(d.KodeAkun) AS KodeAkun,
            TRIM(d.thn_masuk) AS thn_masuk,
            TRIM(d.nominal) AS nominal,
            TRIM(d.NamaAkun) AS NamaAkun,
            TRIM(d.NoRek) AS NoRek,
            TRIM(k.kelas) AS nama_kelas,
            TRIM(k.jenjang) AS jenjang,
            TRIM(k.unit) AS unit
        FROM u_daftar_harga d
        LEFT JOIN mst_kelas k ON k.id = d.kode_prod
        WHERE d.urut = :urut
    ");

    $stmt->execute([":urut" => $urut]);

    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            "status" => 404,
            "message" => "Data beban post tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $row;
}

function getFilterBebanPost(): array
{
    $pdo = dbConnectPdo();

    $stmtThn = $pdo->prepare("
        SELECT urut, TRIM(thn_aka) AS thn_aka
        FROM mst_thn_aka
        WHERE thn_aka IS NOT NULL AND TRIM(thn_aka) != ''
        ORDER BY urut DESC
    ");
    $stmtThn->execute();
    $thn_masuk = $stmtThn->fetchAll();

    $stmtKelas = $pdo->prepare("
        SELECT id, TRIM(kelas) AS kelas, TRIM(unit) AS unit, TRIM(jenjang) AS jenjang, TRIM(kelompok) AS kelompok
        FROM mst_kelas
        WHERE kelas IS NOT NULL AND TRIM(kelas) != ''
        ORDER BY unit ASC, kelas ASC
    ");
    $stmtKelas->execute();
    $kelas = $stmtKelas->fetchAll();

    $stmtAkun = $pdo->prepare("
        SELECT TRIM(KodeAkun) AS KodeAkun, TRIM(NamaAkun) AS NamaAkun
        FROM u_akun
        WHERE KodeAkun IS NOT NULL AND TRIM(KodeAkun) != ''
        ORDER BY KodeAkun ASC
    ");
    $stmtAkun->execute();
    $akun = $stmtAkun->fetchAll();

    return [
        "thn_masuk" => $thn_masuk,
        "kelas"     => $kelas,
        "akun"      => $akun,
    ];
}

function createBebanPost(array $req): array
{
    $kode_fak  = trim((string) ($req["kode_fak"] ?? ""));
    $kode_prod = trim((string) ($req["kode_prod"] ?? ""));
    $KodeAkun  = trim((string) ($req["KodeAkun"] ?? ""));
    $thn_masuk = trim((string) ($req["thn_masuk"] ?? ""));
    $nominal   = trim((string) ($req["nominal"] ?? ""));
    $NamaAkun  = trim((string) ($req["NamaAkun"] ?? ""));

    if ($kode_prod === "" || $KodeAkun === "" || $thn_masuk === "" || $nominal === "") {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "Field kode_prod, KodeAkun, thn_masuk, nominal wajib diisi"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $checkKelas = $pdo->prepare("SELECT id FROM mst_kelas WHERE id = :id");
    $checkKelas->execute([":id" => (int) $kode_prod]);

    if (!$checkKelas->fetch()) {
        http_response_code(404);
        echo json_encode([
            "status" => 404,
            "message" => "Kelas tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $checkThn = $pdo->prepare("SELECT thn_aka FROM mst_thn_aka WHERE TRIM(thn_aka) = :thn_masuk");
    $checkThn->execute([":thn_masuk" => $thn_masuk]);

    if (!$checkThn->fetch()) {
        http_response_code(404);
        echo json_encode([
            "status" => 404,
            "message" => "Tahun akademik tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $checkAkun = $pdo->prepare("SELECT KodeAkun, NamaAkun, NoRek FROM u_akun WHERE TRIM(KodeAkun) = :KodeAkun");
    $checkAkun->execute([":KodeAkun" => $KodeAkun]);
    $akun = $checkAkun->fetch();

    $namaAkunFinal = $akun ? trim((string) ($akun["NamaAkun"] ?? "")) : $NamaAkun;
    $noRekFinal    = $akun ? trim((string) ($akun["NoRek"] ?? "")) : "";

    $stmt = $pdo->prepare("
        INSERT INTO u_daftar_harga (kode_fak, kode_prod, KodeAkun, thn_masuk, nominal, NamaAkun, NoRek)
        VALUES (:kode_fak, :kode_prod, :KodeAkun, :thn_masuk, :nominal, :NamaAkun, :NoRek)
    ");

    $stmt->execute([
        ":kode_fak"  => $kode_fak !== "" ? $kode_fak : null,
        ":kode_prod" => $kode_prod,
        ":KodeAkun"  => $KodeAkun,
        ":thn_masuk" => $thn_masuk,
        ":nominal"   => $nominal,
        ":NamaAkun"  => $namaAkunFinal !== "" ? $namaAkunFinal : null,
        ":NoRek"     => $noRekFinal !== "" ? $noRekFinal : null,
    ]);

    $newUrut = (int) $pdo->lastInsertId();

    return [
        "urut"      => $newUrut,
        "kode_fak"  => $kode_fak !== "" ? $kode_fak : null,
        "kode_prod" => $kode_prod,
        "KodeAkun"  => $KodeAkun,
        "NamaAkun"  => $namaAkunFinal !== "" ? $namaAkunFinal : null,
        "NoRek"     => $noRekFinal !== "" ? $noRekFinal : null,
        "thn_masuk" => $thn_masuk,
        "nominal"   => $nominal,
    ];
}

function exportSiswa(array $req): void
{
    $pdo = dbConnectPdo();

    $where = [];
    $params = [];

    if (!empty($req["DESC04"])) {
        $where[] = "TRIM(DESC04) = :DESC04";
        $params[":DESC04"] = trim($req["DESC04"]);
    }

    if (!empty($req["CODE02"])) {
        $where[] = "TRIM(CODE02) = :CODE02";
        $params[":CODE02"] = trim($req["CODE02"]);
    }

    if (!empty($req["DESC02"])) {
        $where[] = "TRIM(DESC02) = :DESC02";
        $params[":DESC02"] = trim($req["DESC02"]);
    }

    if (isset($req["STCUST"]) && $req["STCUST"] !== "") {
        $where[] = "STCUST = :STCUST";
        $params[":STCUST"] = trim($req["STCUST"]);
    }

    $sql = "
        SELECT
            TRIM(NOCUST)             AS NIS,
            TRIM(NMCUST)             AS Nama,
            TRIM(NUM2ND)             AS NODAF,
            TRIM(CODE02)             AS UNIT,
            TRIM(CODE03)             AS KELAS,
            TRIM(DESC03)             AS KELOMPOK,
            TRIM(DESC04)             AS ANGKATAN,
            TRIM(CODE04)             AS GENDER,
            TRIM(DESC05)             AS ALAMAT,
            TRIM(GENUS)              AS AYAH,
            TRIM(GENUS1)             AS IBU,
            TRIM(EksternalInternal)  AS EKSINT,
            TRIM(GENUSContact)       AS KontakWali,
            TRIM(GetWisma)           AS WISMA
        FROM scctcust
    ";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY NMCUST ASC";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $filename = "export_siswa_" . date("Ymd_His") . ".csv";

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    $out = fopen("php://output", "w");

    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    $headers = ["NIS", "Nama", "NODAF", "UNIT", "KELAS", "KELOMPOK", "ANGKATAN", "GENDER", "ALAMAT", "AYAH", "IBU", "EKSINT", "KontakWali", "WISMA"];
    fputcsv($out, $headers);

    foreach ($rows as $row) {
        fputcsv($out, [
            $row["NIS"],
            $row["Nama"],
            $row["NODAF"],
            $row["UNIT"],
            $row["KELAS"],
            $row["KELOMPOK"],
            $row["ANGKATAN"],
            $row["GENDER"],
            $row["ALAMAT"],
            $row["AYAH"],
            $row["IBU"],
            $row["EKSINT"],
            $row["KontakWali"],
            $row["WISMA"],
        ]);
    }

    fclose($out);
    exit;
}

function importSiswa(array $req): array
{
    if (empty($_FILES["file"]["tmp_name"])) {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "File tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $file     = $_FILES["file"]["tmp_name"];
    $origName = strtolower($_FILES["file"]["name"] ?? "");
    $ext      = pathinfo($origName, PATHINFO_EXTENSION);

    if (!in_array($ext, ["xls", "xlsx", "csv"], true)) {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "File harus berformat XLS, XLSX, atau CSV"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_FILES["file"]["size"] > 1024 * 1024) {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "Ukuran file tidak boleh lebih dari 1MB"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $rows = [];

    if ($ext === "csv") {
        $handle = fopen($file, "r");

        if ($handle === false) {
            http_response_code(500);
            echo json_encode([
                "status" => 500,
                "message" => "Gagal membaca file"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        while (($csvRow = fgetcsv($handle)) !== false) {
            $rows[] = $csvRow;
        }

        fclose($handle);
    } else {
        $simplexlsxPath = __DIR__ . "/config/SimpleXLSX.php";

        if (!file_exists($simplexlsxPath)) {
            http_response_code(500);
            echo json_encode([
                "status" => 500,
                "message" => "Library SimpleXLSX.php tidak ditemukan di config/"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        require_once $simplexlsxPath;
        $xlsx = \Shuchkin\SimpleXLSX::parse($file);

        if (!$xlsx) {
            http_response_code(422);
            echo json_encode([
                "status" => 422,
                "message" => "Gagal membaca file Excel: " . SimpleXLSX::parseError()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        foreach ($xlsx->rows() as $xlsRow) {
            $rows[] = array_values($xlsRow);
        }
    }

    if (count($rows) < 2) {
        http_response_code(422);
        echo json_encode([
            "status" => 422,
            "message" => "File kosong atau tidak ada data"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $colMap = [];
    foreach ($rows[0] as $idx => $col) {
        $colMap[strtoupper(trim((string) $col))] = $idx;
    }

    $required = ["NIS", "KONTAKWALI"];
    foreach ($required as $col) {
        if (!isset($colMap[$col])) {
            http_response_code(422);
            echo json_encode([
                "status"  => 422,
                "message" => "Kolom wajib tidak ditemukan: " . $col
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $pdo = dbConnectPdo();

    $inserted = 0;
    $updated  = 0;
    $skipped  = 0;
    $errors   = [];

    $colGet = fn(string $key, array $row) => isset($colMap[$key]) ? trim((string) ($row[$colMap[$key]] ?? "")) : "";

    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        $nis = $colGet("NIS", $row);
        if ($nis === "") {
            $skipped++;
            continue;
        }

        $nama       = $colGet("NAMA", $row);
        $nodaf      = $colGet("NODAF", $row);
        $unit       = $colGet("UNIT", $row);
        $kelas      = $colGet("KELAS", $row);
        $kelompok   = $colGet("KELOMPOK", $row);
        $angkatan   = $colGet("ANGKATAN", $row);
        $gender     = $colGet("GENDER", $row);
        $alamat     = $colGet("ALAMAT", $row);
        $ayah       = $colGet("AYAH", $row);
        $ibu        = $colGet("IBU", $row);
        $eksint     = $colGet("EKSINT", $row);
        $kontakwali = $colGet("KONTAKWALI", $row);
        $wisma      = $colGet("WISMA", $row);

        try {
            $check = $pdo->prepare("SELECT 1 FROM scctcust WHERE TRIM(NOCUST) = :nis LIMIT 1");
            $check->execute([":nis" => $nis]);
            $existing = $check->fetch();

            if ($existing) {
                $upd = $pdo->prepare("
                    UPDATE scctcust SET
                        NMCUST            = :NMCUST,
                        NUM2ND            = :NUM2ND,
                        CODE02            = :CODE02,
                        CODE03            = :CODE03,
                        DESC03            = :DESC03,
                        DESC04            = :DESC04,
                        CODE04            = :CODE04,
                        DESC05            = :DESC05,
                        GENUS             = :GENUS,
                        GENUS1            = :GENUS1,
                        EksternalInternal = :EksternalInternal,
                        GENUSContact      = :GENUSContact,
                        GetWisma          = :GetWisma,
                        LastUpdate        = NOW()
                    WHERE TRIM(NOCUST) = :nis
                ");

                $upd->execute([
                    ":NMCUST"            => $nama !== "" ? $nama : null,
                    ":NUM2ND"            => $nodaf !== "" ? $nodaf : null,
                    ":CODE02"            => $unit !== "" ? $unit : null,
                    ":CODE03"            => $kelas !== "" ? $kelas : null,
                    ":DESC03"            => $kelompok !== "" ? $kelompok : null,
                    ":DESC04"            => $angkatan !== "" ? $angkatan : null,
                    ":CODE04"            => $gender !== "" ? $gender : null,
                    ":DESC05"            => $alamat !== "" ? $alamat : null,
                    ":GENUS"             => $ayah !== "" ? $ayah : null,
                    ":GENUS1"            => $ibu !== "" ? $ibu : null,
                    ":EksternalInternal" => $eksint !== "" ? $eksint : null,
                    ":GENUSContact"      => $kontakwali !== "" ? $kontakwali : null,
                    ":GetWisma"          => $wisma !== "" ? $wisma : null,
                    ":nis"               => $nis,
                ]);

                $updated++;
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO scctcust
                        (NOCUST, NMCUST, NUM2ND, CODE02, CODE03, DESC03, DESC04, CODE04, DESC05, GENUS, GENUS1, EksternalInternal, GENUSContact, GetWisma, LastUpdate)
                    VALUES
                        (:NOCUST, :NMCUST, :NUM2ND, :CODE02, :CODE03, :DESC03, :DESC04, :CODE04, :DESC05, :GENUS, :GENUS1, :EksternalInternal, :GENUSContact, :GetWisma, NOW())
                ");

                $ins->execute([
                    ":NOCUST"            => $nis,
                    ":NMCUST"            => $nama !== "" ? $nama : null,
                    ":NUM2ND"            => $nodaf !== "" ? $nodaf : null,
                    ":CODE02"            => $unit !== "" ? $unit : null,
                    ":CODE03"            => $kelas !== "" ? $kelas : null,
                    ":DESC03"            => $kelompok !== "" ? $kelompok : null,
                    ":DESC04"            => $angkatan !== "" ? $angkatan : null,
                    ":CODE04"            => $gender !== "" ? $gender : null,
                    ":DESC05"            => $alamat !== "" ? $alamat : null,
                    ":GENUS"             => $ayah !== "" ? $ayah : null,
                    ":GENUS1"            => $ibu !== "" ? $ibu : null,
                    ":EksternalInternal" => $eksint !== "" ? $eksint : null,
                    ":GENUSContact"      => $kontakwali !== "" ? $kontakwali : null,
                    ":GetWisma"          => $wisma !== "" ? $wisma : null,
                ]);

                $inserted++;
            }
        } catch (Throwable $e) {
            $errors[] = ["nis" => $nis, "error" => $e->getMessage()];
        }
    }

    return [
        "inserted" => $inserted,
        "updated"  => $updated,
        "skipped"  => $skipped,
        "errors"   => $errors,
    ];
}

function getSettingAtributSiswa(array $req): array
{
    $pdo = dbConnectPdo();
    $search = trim((string) ($req["search"] ?? ""));
    $limit = max(1, min(500, (int) ($req["limit"] ?? 200)));
    $offset = max(0, (int) ($req["offset"] ?? 0));

    $where = [];
    $params = [];
    if ($search !== "") {
        $where[] = "(TRIM(NOCUST) LIKE :search OR TRIM(NMCUST) LIKE :search OR TRIM(GENUSContact) LIKE :search)";
        $params[":search"] = "%" . $search . "%";
    }

    $sql = "
        SELECT
            TRIM(NOCUST) AS nis,
            TRIM(NMCUST) AS nama,
            TRIM(CODE04) AS gender,
            TRIM(DESC05) AS alamat,
            TRIM(GENUS) AS ayah,
            TRIM(GENUS1) AS ibu,
            TRIM(GENUSContact) AS kontak,
            TRIM(GetWisma) AS wisma,
            TRIM(EksternalInternal) AS eksint
        FROM scctcust
    ";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY NMCUST ASC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function importSettingAtributSiswa(array $req): array
{
    if (empty($_FILES["file"]["tmp_name"])) {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "File tidak ditemukan"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $file = $_FILES["file"]["tmp_name"];
    $origName = strtolower($_FILES["file"]["name"] ?? "");
    $ext = pathinfo($origName, PATHINFO_EXTENSION);
    if (!in_array($ext, ["xls", "xlsx", "csv"], true)) {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "File harus berformat XLS, XLSX, atau CSV"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $rows = [];
    if ($ext === "csv") {
        $h = fopen($file, "r");
        if ($h === false) {
            http_response_code(500);
            echo json_encode(["status" => 500, "message" => "Gagal membaca file"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        while (($r = fgetcsv($h)) !== false) {
            $rows[] = $r;
        }
        fclose($h);
    } else {
        $simplexlsxPath = __DIR__ . "/config/SimpleXLSX.php";
        if (!file_exists($simplexlsxPath)) {
            http_response_code(500);
            echo json_encode(["status" => 500, "message" => "Library SimpleXLSX.php tidak ditemukan di config/"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        require_once $simplexlsxPath;
        $xlsx = \Shuchkin\SimpleXLSX::parse($file);
        if (!$xlsx) {
            http_response_code(422);
            echo json_encode(["status" => 422, "message" => "Gagal membaca file Excel"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        foreach ($xlsx->rows() as $rr) {
            $rows[] = array_values($rr);
        }
    }

    if (count($rows) < 2) {
        return ["inserted" => 0, "updated" => 0, "skipped" => 0, "errors" => []];
    }

    $header = [];
    foreach ($rows[0] as $idx => $col) {
        $header[strtoupper(trim((string) $col))] = $idx;
    }
    if (!isset($header["NIS"])) {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "Kolom wajib tidak ditemukan: NIS"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $colGet = fn(array $row, string $k) => isset($header[$k]) ? trim((string) ($row[$header[$k]] ?? "")) : "";
    $pdo = dbConnectPdo();
    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];

    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $nis = $colGet($row, "NIS");
        if ($nis === "") {
            $skipped++;
            continue;
        }

        $nama   = $colGet($row, "NAMA");
        $gender = $colGet($row, "JENIS KELAMIN");
        if ($gender === "") {
            $gender = $colGet($row, "GENDER");
        }
        $alamat = $colGet($row, "ALAMAT");
        $ayah   = $colGet($row, "AYAH");
        $ibu    = $colGet($row, "IBU");
        $kontak = $colGet($row, "KONTAK");
        if ($kontak === "") {
            $kontak = $colGet($row, "KONTAKWALI");
        }
        $wisma  = $colGet($row, "WISMA");
        $eksint = $colGet($row, "EKSINT");

        try {
            $q = $pdo->prepare("SELECT 1 FROM scctcust WHERE TRIM(NOCUST)=:nis LIMIT 1");
            $q->execute([":nis" => $nis]);
            $exists = $q->fetch();

            if ($exists) {
                $u = $pdo->prepare("
                    UPDATE scctcust SET
                        NMCUST = COALESCE(NULLIF(:NMCUST,''), NMCUST),
                        CODE04 = COALESCE(NULLIF(:CODE04,''), CODE04),
                        DESC05 = COALESCE(NULLIF(:DESC05,''), DESC05),
                        GENUS = COALESCE(NULLIF(:GENUS,''), GENUS),
                        GENUS1 = COALESCE(NULLIF(:GENUS1,''), GENUS1),
                        GENUSContact = COALESCE(NULLIF(:GENUSContact,''), GENUSContact),
                        GetWisma = COALESCE(NULLIF(:GetWisma,''), GetWisma),
                        EksternalInternal = COALESCE(NULLIF(:EksternalInternal,''), EksternalInternal),
                        LastUpdate = NOW()
                    WHERE TRIM(NOCUST)=:nis
                ");
                $u->execute([
                    ":NMCUST" => $nama,
                    ":CODE04" => $gender,
                    ":DESC05" => $alamat,
                    ":GENUS" => $ayah,
                    ":GENUS1" => $ibu,
                    ":GENUSContact" => $kontak,
                    ":GetWisma" => $wisma,
                    ":EksternalInternal" => $eksint,
                    ":nis" => $nis
                ]);
                $updated++;
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO scctcust (NOCUST, NMCUST, CODE04, DESC05, GENUS, GENUS1, GENUSContact, GetWisma, EksternalInternal, LastUpdate)
                    VALUES (:NOCUST, :NMCUST, :CODE04, :DESC05, :GENUS, :GENUS1, :GENUSContact, :GetWisma, :EksternalInternal, NOW())
                ");
                $ins->execute([
                    ":NOCUST" => $nis,
                    ":NMCUST" => $nama !== "" ? $nama : null,
                    ":CODE04" => $gender !== "" ? $gender : null,
                    ":DESC05" => $alamat !== "" ? $alamat : null,
                    ":GENUS" => $ayah !== "" ? $ayah : null,
                    ":GENUS1" => $ibu !== "" ? $ibu : null,
                    ":GENUSContact" => $kontak !== "" ? $kontak : null,
                    ":GetWisma" => $wisma !== "" ? $wisma : null,
                    ":EksternalInternal" => $eksint !== "" ? $eksint : null
                ]);
                $inserted++;
            }
        } catch (Throwable $e) {
            $errors[] = ["nis" => $nis, "error" => $e->getMessage()];
        }
    }

    return ["inserted" => $inserted, "updated" => $updated, "skipped" => $skipped, "errors" => $errors];
}

function getSiswaByKelas(array $req): array
{
    $pdo         = dbConnectPdo();
    $kelasSumber = trim((string) ($req["kelas_sumber"] ?? ""));
    if ($kelasSumber === "") {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "kelas_sumber wajib diisi"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmtKelas = $pdo->prepare("SELECT id, kelas, unit, kelompok, jenjang FROM mst_kelas WHERE id = :id");
    $stmtKelas->execute([":id" => (int) $kelasSumber]);
    $kelasRow = $stmtKelas->fetch();
    if (!$kelasRow) {
        http_response_code(404);
        echo json_encode(["status" => 404, "message" => "Kelas sumber tidak ditemukan"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $where  = [];
    $params = [];
    $where[]           = "TRIM(CODE03) = :CODE03";
    $params[":CODE03"] = (string) $kelasRow["id"];
    if (!empty($req["search"])) {
        $where[]            = "(TRIM(NMCUST) LIKE :search OR TRIM(NOCUST) LIKE :search2)";
        $params[":search"]  = "%" . trim($req["search"]) . "%";
        $params[":search2"] = "%" . trim($req["search"]) . "%";
    }
    $whereStr  = implode(" AND ", $where);
    $stmtCount = $pdo->prepare("SELECT COUNT(*) AS total FROM scctcust WHERE $whereStr");
    foreach ($params as $key => $val) $stmtCount->bindValue($key, $val, PDO::PARAM_STR);
    $stmtCount->execute();
    $totalRow = $stmtCount->fetch();
    $limit    = min((int) ($req["limit"]  ?? 50), 200);
    $offset   = max((int) ($req["offset"] ?? 0), 0);
    $sql = "
        SELECT
            CUSTID,
            TRIM(NOCUST) AS NOCUST,
            TRIM(NMCUST) AS NMCUST,
            TRIM(NUM2ND) AS NUM2ND,
            TRIM(CODE02) AS CODE02,
            TRIM(DESC02) AS DESC02,
            TRIM(CODE03) AS CODE03,
            TRIM(DESC03) AS DESC03,
            TRIM(DESC04) AS DESC04,
            STCUST
        FROM scctcust
        WHERE $whereStr
        ORDER BY NMCUST ASC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) $stmt->bindValue($key, $val, PDO::PARAM_STR);
    $stmt->bindValue(":limit",  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    return [
        "kelas_sumber" => $kelasRow,
        "total"        => (int) ($totalRow["total"] ?? 0),
        "data"         => $stmt->fetchAll(),
    ];
}

function pindahKelas(array $req): array
{
    $kelasSumber    = trim((string) ($req["kelas_sumber"] ?? ""));
    $kelasTujuan    = trim((string) ($req["kelas_tujuan"] ?? ""));
    $modePemindahan = trim((string) ($req["mode"]         ?? ""));
    if ($kelasSumber === "" || $kelasTujuan === "") {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "kelas_sumber dan kelas_tujuan wajib diisi"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($kelasSumber === $kelasTujuan) {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "Kelas sumber dan tujuan tidak boleh sama"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!in_array($modePemindahan, ["semua", "pilihan"], true)) {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "mode harus bernilai 'semua' atau 'pilihan'"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $pdo        = dbConnectPdo();
    $stmtSumber = $pdo->prepare("SELECT id, kelas, unit, kelompok, jenjang FROM mst_kelas WHERE id = :id");
    $stmtSumber->execute([":id" => (int) $kelasSumber]);
    $kelasSumberRow = $stmtSumber->fetch();
    if (!$kelasSumberRow) {
        http_response_code(404);
        echo json_encode(["status" => 404, "message" => "Kelas sumber tidak ditemukan"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmtTujuan = $pdo->prepare("SELECT id, kelas, unit, kelompok, jenjang FROM mst_kelas WHERE id = :id");
    $stmtTujuan->execute([":id" => (int) $kelasTujuan]);
    $kelasTujuanRow = $stmtTujuan->fetch();
    if (!$kelasTujuanRow) {
        http_response_code(404);
        echo json_encode(["status" => 404, "message" => "Kelas tujuan tidak ditemukan"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $kelasTujuanNama = trim((string) ($kelasTujuanRow["kelas"]    ?? ""));
    $idTujuan        = (string) $kelasTujuanRow["id"];
    $kelompokTujuan  = trim((string) ($kelasTujuanRow["kelompok"] ?? ""));
    if ($modePemindahan === "semua") {
        $idSumber = (string) $kelasSumberRow["id"];
        $stmt     = $pdo->prepare("
            UPDATE scctcust SET
                DESC02     = :DESC02,
                CODE03     = :CODE03,
                DESC03     = :DESC03,
                LastUpdate = NOW()
            WHERE TRIM(CODE03) = :CODE03_lama
        ");
        $stmt->execute([
            ":DESC02"      => $kelasTujuanNama,
            ":CODE03"      => $idTujuan,
            ":DESC03"      => $kelompokTujuan,
            ":CODE03_lama" => $idSumber,
        ]);
        return [
            "mode"           => "semua",
            "kelas_sumber"   => $kelasSumberRow,
            "kelas_tujuan"   => $kelasTujuanRow,
            "total_dipindah" => $stmt->rowCount(),
        ];
    }
    $custids = $req["custids"] ?? [];
    if (!is_array($custids) || count($custids) === 0) {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "custids wajib diisi untuk mode pilihan"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $custids = array_values(array_filter(array_map("intval", $custids), fn($v) => $v > 0));
    if (count($custids) === 0) {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "custids tidak valid"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $placeholders = implode(",", array_fill(0, count($custids), "?"));
    $stmt         = $pdo->prepare("
        UPDATE scctcust SET
            DESC02     = ?,
            CODE03     = ?,
            DESC03     = ?,
            LastUpdate = NOW()
        WHERE CUSTID IN ($placeholders)
    ");
    $stmt->execute(array_merge([$kelasTujuanNama, $idTujuan, $kelompokTujuan], $custids));
    return [
        "mode"           => "pilihan",
        "kelas_tujuan"   => $kelasTujuanRow,
        "total_dipindah" => $stmt->rowCount(),
        "custids"        => $custids,
    ];
}

function getFilterBuatTagihan(): array
{
    $t0 = microtime(true);
    $pdo = dbConnectPdo();

    $stmtThnAka = $pdo->prepare("
        SELECT urut, TRIM(thn_aka) AS thn_aka
        FROM mst_thn_aka
        WHERE thn_aka IS NOT NULL AND TRIM(thn_aka) != ''
        ORDER BY urut DESC
    ");
    $stmtThnAka->execute();
    $thn_akademik = $stmtThnAka->fetchAll();

    $stmtAngkatan = $pdo->prepare("
        SELECT urut, TRIM(thn_aka) AS thn_angkatan
        FROM mst_thn_aka
        WHERE thn_aka IS NOT NULL AND TRIM(thn_aka) != ''
        ORDER BY urut DESC
    ");
    $stmtAngkatan->execute();
    $thn_angkatan = array_column($stmtAngkatan->fetchAll(), 'thn_angkatan');

    $stmtKelas = $pdo->prepare("
        SELECT id, TRIM(kelas) AS kelas, TRIM(jenjang) AS jenjang, TRIM(unit) AS unit, TRIM(kelompok) AS kelompok
        FROM mst_kelas
        WHERE kelas IS NOT NULL AND TRIM(kelas) != ''
        ORDER BY unit ASC, kelas ASC
    ");
    $stmtKelas->execute();
    $kelas = $stmtKelas->fetchAll();

    $stmtTagihan = $pdo->prepare("
        SELECT TRIM(tagihan) AS tagihan
        FROM mst_tagihan
        WHERE tagihan IS NOT NULL AND TRIM(tagihan) != ''
        ORDER BY urut ASC
    ");
    $stmtTagihan->execute();
    $tagihan = array_column($stmtTagihan->fetchAll(), 'tagihan');

    $ms = round((microtime(true) - $t0) * 1000, 2);
    if ($ms >= 200) {
        writeLog(['scope' => 'getFilterBuatTagihan', 'ms' => $ms, 'note' => 'opsi filter halaman penerimaan / buat tagihan']);
    }

    return [
        'thn_akademik' => $thn_akademik,
        'thn_angkatan' => $thn_angkatan,
        'kelas'        => $kelas,
        'tagihan'      => $tagihan,
    ];
}

/**
 * Opsi filter halaman Rekap Penerimaan: sama getFilterBuatTagihan + daftar Tingkat (unit unik mst_kelas).
 * Nama tagihan tetap dari mst_tagihan.
 */
function getRekapPenerimaanFilterShell(): array
{
    $base = getFilterBuatTagihan();
    $pdo = dbConnectPdo();
    $tingkat = [];
    try {
        $st = $pdo->query("
            SELECT DISTINCT TRIM(unit) AS u
            FROM mst_kelas
            WHERE unit IS NOT NULL AND TRIM(unit) != ''
            ORDER BY u ASC
        ");
        if ($st) {
            $tingkat = array_values(array_filter(array_column($st->fetchAll(PDO::FETCH_ASSOC) ?: [], 'u')));
        }
    } catch (Throwable $e) {
        $tingkat = [];
    }
    $base['tingkat'] = $tingkat;

    return $base;
}

function getBuatTagihan(array $req): array
{
    $thn_akademik = trim((string) ($req['thn_akademik'] ?? ''));
    $thn_angkatan = trim((string) ($req['thn_angkatan'] ?? ''));
    $kelas_id     = trim((string) ($req['kelas_id']     ?? ''));
    $tagihan      = trim((string) ($req['tagihan']      ?? ''));
    writeLog([
        'scope' => 'getBuatTagihan:start',
        'thn_akademik' => $thn_akademik,
        'thn_angkatan' => $thn_angkatan,
        'kelas_id' => $kelas_id,
        'tagihan' => $tagihan,
    ]);

    $pdo = dbConnectPdo();

    $thnAngkatanBase = trim((string) preg_replace('/\s*-\s*.*/', '', $thn_angkatan));

    // Fungsi/BILLAC berbasis rule tagihan:
    // - bulanan (nama bulan) => YYYYMM sesuai bulan pada nama tagihan
    // - selain bulanan => YYYY + bulan sekarang
    $fungsi = resolveBillacPeriodeByTagihan($tagihan, trim((string) ($req['fungsi'] ?? '')));
    writeLog([
        'scope' => 'getBuatTagihan:fungsi',
        'fungsi' => $fungsi,
        'thn_angkatan_base' => $thnAngkatanBase,
    ]);

    $kelasRow = [];
    if ($kelas_id !== '') {
        $stmtKelas = $pdo->prepare("SELECT id, kelas, jenjang, unit, kelompok FROM mst_kelas WHERE id = :id");
        $stmtKelas->execute([':id' => (int) $kelas_id]);
        $kelasRow = $stmtKelas->fetch() ?: [];
    }

    $kelasNama = trim((string) ($kelasRow['kelas'] ?? ''));
    $jenjangNama = trim((string) ($kelasRow['jenjang'] ?? ''));
    $unitNama = trim((string) ($kelasRow['unit'] ?? ''));
    $kelompokKodeProd = trim((string) ($kelasRow['kelompok'] ?? ''));

    $wheresSiswa = ["1=1"];
    $paramsSiswa = [];

    if ($thn_angkatan !== '') {
        $wheresSiswa[] = "(TRIM(c.DESC04) = :thn_angkatan OR TRIM(c.DESC04) = :thn_angkatan_base)";
        $paramsSiswa[':thn_angkatan'] = $thn_angkatan;
        $paramsSiswa[':thn_angkatan_base'] = $thnAngkatanBase;
    }

    if ($kelas_id !== '') {
        if (!empty($kelasRow)) {
            $wheresSiswa[] = "(
                TRIM(c.CODE03) = :kelas_id
                OR (
                    TRIM(c.DESC02) = :kelas_nama
                    AND TRIM(c.DESC03) = :jenjang_nama
                    AND TRIM(c.CODE02) = :unit_nama
                )
            )";
            $paramsSiswa[':kelas_id'] = $kelas_id;
            $paramsSiswa[':kelas_nama'] = $kelasNama;
            $paramsSiswa[':jenjang_nama'] = $jenjangNama;
            $paramsSiswa[':unit_nama'] = $unitNama;
        } else {
            $wheresSiswa[] = "1=0";
        }
    }

    if (!empty($req['search'])) {
        $wheresSiswa[] = "(TRIM(c.NMCUST) LIKE :search OR TRIM(c.NOCUST) LIKE :search2)";
        $paramsSiswa[':search']  = '%' . trim($req['search']) . '%';
        $paramsSiswa[':search2'] = '%' . trim($req['search']) . '%';
    }

    // fungsi hanya sebagai informasi/atribut tagihan, bukan filter pencarian siswa.

    $sqlCount = "
        SELECT COUNT(*) AS total
        FROM scctcust c
        WHERE " . implode(' AND ', $wheresSiswa);
    $stmtCount = $pdo->prepare($sqlCount);
    foreach ($paramsSiswa as $k => $v) {
        $stmtCount->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmtCount->execute();
    $totalSiswa = (int) ($stmtCount->fetchColumn() ?: 0);

    $sqlSiswa = "
        SELECT
            c.CUSTID,
            TRIM(c.NOCUST) AS NIS,
            TRIM(c.NMCUST) AS NAMA,
            TRIM(c.CODE01) AS CODE01,
            TRIM(c.CODE03) AS kelas_id,
            COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC02)) AS KELAS,
            COALESCE(NULLIF(TRIM(mk.jenjang), ''), TRIM(c.DESC03)) AS JENJANG,
            TRIM(c.DESC04) AS ANGKATAN,
            TRIM(c.CODE02) AS unit
        FROM scctcust c
        LEFT JOIN mst_kelas mk ON CAST(mk.id AS CHAR) = TRIM(c.CODE03)
        WHERE " . implode(' AND ', $wheresSiswa) . "
        ORDER BY c.NMCUST ASC
        LIMIT :limit OFFSET :offset
    ";

    $limit  = min((int) ($req['limit']  ?? 50), 200);
    $offset = max((int) ($req['offset'] ?? 0), 0);

    $stmtSiswa = $pdo->prepare($sqlSiswa);
    foreach ($paramsSiswa as $k => $v) {
        $stmtSiswa->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmtSiswa->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmtSiswa->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtSiswa->execute();
    $siswa = $stmtSiswa->fetchAll();

    $daftar_harga = [];
    if ($kelas_id !== '') {
        $resolvedKodeProd = $kelas_id;
        $sqlDaftar = "
            SELECT
                d.urut,
                TRIM(d.KodeAkun) AS KodeAkun,
                COALESCE(
                    NULLIF(TRIM(d.NamaAkun), ''),
                    (
                        SELECT TRIM(a.NamaAkun)
                        FROM u_akun a
                        WHERE TRIM(a.KodeAkun) = TRIM(d.KodeAkun)
                        LIMIT 1
                    )
                ) AS NamaAkun,
                TRIM(d.nominal)  AS nominal,
                TRIM(d.NoRek)    AS NoRek
            FROM u_daftar_harga d
            WHERE TRIM(d.kode_prod) = :kode_prod
        ";
        $paramsDaftar = [':kode_prod' => $resolvedKodeProd];

        if ($thn_angkatan !== '' || $thnAngkatanBase !== '') {
            $sqlDaftar .= " AND (
                REPLACE(TRIM(d.thn_masuk), ' ', '') = REPLACE(TRIM(:thn_angkatan_full), ' ', '')
                OR REPLACE(TRIM(d.thn_masuk), ' ', '') = REPLACE(TRIM(:thn_angkatan_base_eq), ' ', '')
                OR REPLACE(TRIM(d.thn_masuk), ' ', '') LIKE CONCAT(REPLACE(TRIM(:thn_angkatan_base_like), ' ', ''), '%')
            )";
            $baseAngkatan = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thn_angkatan;
            $paramsDaftar[':thn_angkatan_full'] = $thn_angkatan;
            $paramsDaftar[':thn_angkatan_base_eq'] = $baseAngkatan;
            $paramsDaftar[':thn_angkatan_base_like'] = $baseAngkatan;
        }

        $sqlDaftar .= " ORDER BY d.urut ASC";

        $stmtDaftar = $pdo->prepare($sqlDaftar);
        $stmtDaftar->execute($paramsDaftar);
        $daftar_harga = $stmtDaftar->fetchAll();

        // Bridge mapping: jika kelas_id tidak ketemu sebagai kode_prod,
        // coba temukan kode_prod dari kode_fak (mis. kelas 3 -> kode_fak 03),
        // lalu query final tetap pakai kode_prod.
        if (count($daftar_harga) === 0) {
            $kelasPad2 = str_pad((string) ((int) $kelas_id), 2, '0', STR_PAD_LEFT);
            $sqlResolve = "
                SELECT TRIM(kode_prod) AS kode_prod
                FROM u_daftar_harga
                WHERE TRIM(kode_fak) = :kelas_pad2
            ";
            $paramsResolve = [':kelas_pad2' => $kelasPad2];
            if ($thn_angkatan !== '' || $thnAngkatanBase !== '') {
                $sqlResolve .= " AND (
                    REPLACE(TRIM(thn_masuk), ' ', '') = REPLACE(TRIM(:thn_angkatan_full), ' ', '')
                    OR REPLACE(TRIM(thn_masuk), ' ', '') = REPLACE(TRIM(:thn_angkatan_base_eq), ' ', '')
                    OR REPLACE(TRIM(thn_masuk), ' ', '') LIKE CONCAT(REPLACE(TRIM(:thn_angkatan_base_like), ' ', ''), '%')
                )";
                $baseAngkatanResolve = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thn_angkatan;
                $paramsResolve[':thn_angkatan_full'] = $thn_angkatan;
                $paramsResolve[':thn_angkatan_base_eq'] = $baseAngkatanResolve;
                $paramsResolve[':thn_angkatan_base_like'] = $baseAngkatanResolve;
            }
            $sqlResolve .= " ORDER BY urut ASC LIMIT 1";
            $stmtResolve = $pdo->prepare($sqlResolve);
            $stmtResolve->execute($paramsResolve);
            $resolvedRow = $stmtResolve->fetch();
            $resolvedKodeProd = trim((string) ($resolvedRow['kode_prod'] ?? ''));

            if ($resolvedKodeProd !== '') {
                $paramsDaftar[':kode_prod'] = $resolvedKodeProd;
                $stmtDaftar = $pdo->prepare($sqlDaftar);
                $stmtDaftar->execute($paramsDaftar);
                $daftar_harga = $stmtDaftar->fetchAll();
            }
        }

        // Fallback terakhir: gunakan CODE01 siswa (contoh 001 -> kode_prod 1).
        if (count($daftar_harga) === 0 && count($siswa) > 0) {
            $rawCode01 = trim((string) ($siswa[0]['CODE01'] ?? $siswa[0]['code01'] ?? ''));
            $code01Num = ltrim(preg_replace('/\D+/', '', $rawCode01), '0');
            if ($code01Num !== '') {
                $resolvedKodeProd = $code01Num;
                $paramsDaftar[':kode_prod'] = $resolvedKodeProd;
                $stmtDaftar = $pdo->prepare($sqlDaftar);
                $stmtDaftar->execute($paramsDaftar);
                $daftar_harga = $stmtDaftar->fetchAll();
            }
        }

        writeLog([
            'scope' => 'getBuatTagihan:daftar_harga_params',
            'params' => $paramsDaftar,
            'kelas_kode_prod_fallback' => $kelompokKodeProd,
            'resolved_kode_prod' => $resolvedKodeProd,
            'total_daftar_harga' => count($daftar_harga),
        ]);
    }
    writeLog([
        'scope' => 'getBuatTagihan:result',
        'total_siswa_all' => $totalSiswa,
        'total_siswa' => count($siswa),
        'total_daftar_harga' => count($daftar_harga),
        'kelas_filter' => [
            'kelas_id' => $kelas_id,
            'kelas_nama' => $kelasNama,
            'jenjang_nama' => $jenjangNama,
            'unit_nama' => $unitNama,
        ],
    ]);

    return [
        'kelas'        => $kelasRow,
        'thn_akademik' => $thn_akademik,
        'thn_angkatan' => $thn_angkatan,
        'tagihan'      => $tagihan,
        'fungsi'       => $fungsi,
        'total_siswa'  => $totalSiswa,
        'siswa'        => $siswa,
        'daftar_harga' => $daftar_harga,
    ];
}

function getFungsiBuatTagihan(array $req): array
{
    $thn_akademik = trim((string) ($req['thn_akademik'] ?? ''));
    $tagihan = trim((string) ($req['tagihan'] ?? ''));

    writeLog([
        'scope' => 'getFungsiBuatTagihan:start',
        'thn_akademik' => $thn_akademik,
        'tagihan' => $tagihan,
    ]);

    if ($thn_akademik === '') {
        return ['fungsi' => '', 'source' => 'empty_param'];
    }

    $fungsi = resolveBillacPeriodeByTagihan($tagihan, date('Ym'));
    $source = 'rule.tagihan.periode';

    writeLog([
        'scope' => 'getFungsiBuatTagihan:result',
        'fungsi' => $fungsi,
        'source' => $source,
    ]);

    return ['fungsi' => $fungsi, 'source' => $source];
}

/**
 * Variasi kunci untuk mencocokkan NIS / nomor di excel dengan NOCUST / NUM2ND.
 *
 * @return list<string>
 */
function nisLookupAliasesWs(string $raw): array
{
    $s = trim($raw);
    $keys = [];
    if ($s !== '') {
        $keys[] = $s;
    }
    $digits = preg_replace('/\D+/', '', $s);
    if ($digits !== '') {
        $keys[] = $digits;
        $noLeading = ltrim($digits, '0');
        if ($noLeading !== '') {
            $keys[] = $noLeading;
        }
    }

    return array_values(array_unique(array_filter($keys, static fn($v) => $v !== '')));
}

/**
 * Daftar semua string unik untuk clause IN (NOCUST / NUM2ND).
 *
 * @param list<string> $nisInputs
 * @return list<string>
 */
function expandNisInListWs(array $nisInputs): array
{
    $bucket = [];
    foreach ($nisInputs as $nis) {
        foreach (nisLookupAliasesWs((string) $nis) as $a) {
            $bucket[$a] = true;
        }
    }

    return array_keys($bucket);
}

/**
 * @param array<string, array<string, mixed>> $map
 */
function registerScctcustRowForNisMap(array &$map, array $row): void
{
    $custid = (int) ($row['CUSTID'] ?? 0);
    if ($custid > 0) {
        $map['cid:' . $custid] = $row;
    }
    foreach (nisLookupAliasesWs((string) ($row['NIS_RAW'] ?? '')) as $k) {
        $map[$k] = $row;
    }
    foreach (nisLookupAliasesWs((string) ($row['NUM2ND_RAW'] ?? '')) as $k) {
        $map[$k] = $row;
    }
}

/**
 * @param array<string, array<string, mixed>> $map
 */
function resolveScctcustRowFromNisMap(
    array $map,
    string $nisExcel,
    int $custidHint
): ?array {
    if ($custidHint > 0 && isset($map['cid:' . $custidHint])) {
        return $map['cid:' . $custidHint];
    }
    foreach (nisLookupAliasesWs($nisExcel) as $k) {
        if (isset($map[$k])) {
            return $map[$k];
        }
    }

    return null;
}

/**
 * Cari CUSTID untuk simpan tagihan excel (NOCUST / NUM2ND / hint id dari pratinjau).
 */
function resolveCustIdForTagihanRow(PDO $pdo, string $nis, int $custidHint = 0): int
{
    $nis = trim($nis);

    if ($custidHint > 0 && $nis === '') {
        $st = $pdo->prepare('SELECT CUSTID FROM scctcust WHERE CUSTID = ? LIMIT 1');
        $st->execute([$custidHint]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['CUSTID'] : 0;
    }

    if ($custidHint > 0) {
        $st = $pdo->prepare('
            SELECT CUSTID, TRIM(NOCUST) AS NO, TRIM(NUM2ND) AS ND
            FROM scctcust
            WHERE CUSTID = ?
            LIMIT 1
        ');
        $st->execute([$custidHint]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $ok = false;
            foreach (nisLookupAliasesWs($nis) as $a) {
                if (
                    $a !== ''
                    && ($a === (string) ($row['NO'] ?? '') || $a === (string) ($row['ND'] ?? ''))
                ) {
                    $ok = true;
                    break;
                }
            }

            return $ok ? (int) $row['CUSTID'] : 0;
        }
    }

    if ($nis === '') {
        return 0;
    }

    foreach (nisLookupAliasesWs($nis) as $alias) {
        $st = $pdo->prepare('
            SELECT CUSTID
            FROM scctcust
            WHERE TRIM(NOCUST) = ? OR TRIM(NUM2ND) = ?
            LIMIT 1
        ');
        $st->execute([$alias, $alias]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $cid = (int) $row['CUSTID'];
            if ($custidHint > 0 && $cid !== $custidHint) {
                return 0;
            }

            return $cid;
        }
    }

    return 0;
}

/**
 * Lengkapi baris preview upload tagihan excel:
 * NIS / NUM2ND (NODAF) / petunjuk CUSTID (idcust) -> scctcust + mst_kelas.
 *
 * @param array $req
 * @return array{rows: array<int, array<string, mixed>>}
 */
function enrichTagihanExcelRows(array $req): array
{
    $rowsIn = $req['rows'] ?? [];
    if (!is_array($rowsIn)) {
        return ['rows' => []];
    }

    $normalized = [];
    foreach ($rowsIn as $r) {
        if (!is_array($r)) {
            continue;
        }
        $nis = trim((string) ($r['nis'] ?? ''));
        $custidHint = (int) ($r['custid'] ?? $r['idcust'] ?? $r['IDCUST'] ?? $r['CUSTID'] ?? 0);
        if ($nis === '' && $custidHint <= 0) {
            continue;
        }
        $rawNom = (string) ($r['nominal'] ?? '');
        $nominal = (int) preg_replace('/\D+/', '', $rawNom);
        $normalized[] = [
            'nis'         => $nis,
            'custid_hint' => $custidHint,
            'nominal'     => $nominal,
            'kontak_wali' => trim((string) ($r['kontak_wali'] ?? '')),
        ];
    }

    if (count($normalized) === 0) {
        return ['rows' => []];
    }

    $nisForIn = [];
    foreach ($normalized as $item) {
        if (($item['nis'] ?? '') !== '') {
            $nisForIn[] = $item['nis'];
        }
    }
    $expandedIn = expandNisInListWs($nisForIn);

    $custHints = [];
    foreach ($normalized as $item) {
        $h = (int) ($item['custid_hint'] ?? 0);
        if ($h > 0) {
            $custHints[$h] = true;
        }
    }
    $custIdList = array_map('intval', array_keys($custHints));

    $pdo = dbConnectPdo();
    $whereParts = [];
    $params = [];

    if (count($expandedIn) > 0) {
        // Satu nama placeholder hanya boleh dipakai sekali per query (PDO native MySQL: HY093).
        $phNocust = [];
        $phNum2nd = [];
        foreach ($expandedIn as $i => $val) {
            $pN = ':nx' . $i;
            $pM = ':ny' . $i;
            $phNocust[] = $pN;
            $phNum2nd[] = $pM;
            $params[$pN] = $val;
            $params[$pM] = $val;
        }
        $whereParts[] = '(TRIM(c.NOCUST) IN (' . implode(',', $phNocust) . ') OR TRIM(c.NUM2ND) IN (' . implode(',', $phNum2nd) . '))';
    }

    if (count($custIdList) > 0) {
        $phc = [];
        foreach ($custIdList as $i => $cid) {
            $p = ':cx' . $i;
            $phc[] = $p;
            $params[$p] = $cid;
        }
        $whereParts[] = 'c.CUSTID IN (' . implode(',', $phc) . ')';
    }

    if (count($whereParts) === 0) {
        return ['rows' => []];
    }

    $sql = "
        SELECT
            c.CUSTID,
            TRIM(c.NOCUST) AS NIS_RAW,
            TRIM(c.NUM2ND) AS NUM2ND_RAW,
            TRIM(c.NMCUST) AS NAMA,
            COALESCE(NULLIF(TRIM(mk.unit), ''), TRIM(c.CODE02), '') AS SEKOLAH,
            COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC02), '') AS KELAS,
            COALESCE(NULLIF(TRIM(mk.kelompok), ''), TRIM(c.DESC03), '') AS KELOMPOK
        FROM scctcust c
        LEFT JOIN mst_kelas mk ON CAST(mk.id AS CHAR) = TRIM(c.CODE03)
        WHERE " . implode(' OR ', $whereParts);

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        if (str_starts_with((string) $k, ':cx')) {
            $stmt->bindValue($k, (int) $v, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
    }
    $stmt->execute();
    $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($dbRows as $db) {
        registerScctcustRowForNisMap($map, $db);
    }

    $out = [];
    foreach ($normalized as $base) {
        $nis = $base['nis'];
        $hint = (int) ($base['custid_hint'] ?? 0);
        $m = resolveScctcustRowFromNisMap($map, $nis, $hint);
        if ($m !== null) {
            $out[] = [
                'nis'         => $nis !== '' ? $nis : trim((string) ($m['NIS_RAW'] ?? '')),
                'nominal'     => $base['nominal'],
                'kontak_wali' => $base['kontak_wali'],
                'custid'      => (int) ($m['CUSTID'] ?? 0),
                'nama'        => trim((string) ($m['NAMA'] ?? '')),
                'sekolah'     => trim((string) ($m['SEKOLAH'] ?? '')),
                'kelas'       => trim((string) ($m['KELAS'] ?? '')),
                'kelompok'    => trim((string) ($m['KELOMPOK'] ?? '')),
                'ok'          => true,
            ];
        } else {
            $out[] = [
                'nis'         => $nis,
                'nominal'     => $base['nominal'],
                'kontak_wali' => $base['kontak_wali'],
                'custid'      => 0,
                'nama'        => '',
                'sekolah'     => '',
                'kelas'       => '',
                'kelompok'    => '',
                'ok'          => false,
                'error'       => 'NIS / id tidak ditemukan di scctcust',
            ];
        }
    }

    return ['rows' => $out];
}

function createTagihanExcelUpload(array $req): array
{
    $thn_akademik = trim((string) ($req['thn_akademik'] ?? ''));
    $tagihan      = trim((string) ($req['tagihan'] ?? ''));
    $periode      = trim((string) ($req['periode'] ?? ''));
    $kode_akun    = trim((string) ($req['kode_akun'] ?? ''));
    $rows         = $req['rows'] ?? [];
    $billcdMode   = normalizeBillCdMode((string) ($req['billcd_mode'] ?? 'E'));

    if ($thn_akademik === '' || $periode === '' || $kode_akun === '') {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'thn_akademik, periode, dan kode_akun wajib diisi'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $periode = resolveBillacPeriodeByTagihan($tagihan, $periode);

    if (!is_array($rows) || count($rows) === 0) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'rows tidak boleh kosong'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();
    $stmtAkun = $pdo->prepare("
        SELECT TRIM(KodeAkun) AS KodeAkun, TRIM(NamaAkun) AS NamaAkun
        FROM u_akun
        WHERE TRIM(KodeAkun) = :kode
        LIMIT 1
    ");
    $stmtAkun->execute([':kode' => $kode_akun]);
    $akunRow = $stmtAkun->fetch();
    if (!$akunRow) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'Kode akun (post) tidak ditemukan di u_akun'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $billnm = $tagihan !== '' ? $tagihan : trim((string) ($akunRow['NamaAkun'] ?? ''));

    $stmtInsert = $pdo->prepare("
        INSERT INTO scctbill
            (CUSTID, BILLCD, BILLAC, BILLNM, BILLAM, PAIDST, FSTSBolehBayar, BTA, FTGLTagihan, furutan)
        VALUES
            (:CUSTID, :BILLCD, :BILLAC, :BILLNM, :BILLAM, '0', 1, :BTA, NOW(), :FURUTAN)
    ");
    $stmtBillAa = $pdo->prepare("
        SELECT AA
        FROM scctbill
        WHERE CUSTID = :c AND BILLCD = :b
        ORDER BY AA DESC
        LIMIT 1
    ");
    $detailCustCol = detectScctbillDetailCustColumn($pdo);
    $stmtInsertDetail = $pdo->prepare("
        INSERT INTO scctbill_detail
            (AA, KodePost, BILLAM, {$detailCustCol}, FID, tahun, periode, BILLCD)
        VALUES
            (:AA, :KodePost, :BILLAM, :CUST_VAL, :FID, :tahun, :periode, :BILLCD)
    ");

    $inserted = 0;
    $skipped  = 0;
    $errors   = [];

    $stmtMaxUrutGlobal = $pdo->prepare("SELECT COALESCE(MAX(furutan), 0) AS mx FROM scctbill");
    $nextUrutGlobal = 1;

    $pdo->beginTransaction();

    try {
        $stmtMaxUrutGlobal->execute();
        $mxGlobal = (int) ($stmtMaxUrutGlobal->fetchColumn() ?: 0);
        $nextUrutGlobal = max(1, $mxGlobal + 1);

        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $nis      = trim((string) ($r['nis'] ?? ''));
            $nominal  = (int) ($r['nominal'] ?? 0);
            $custidIn = (int) ($r['custid'] ?? 0);

            if (($nis === '' && $custidIn <= 0) || $nominal <= 0) {
                $errors[] = ['nis' => $nis, 'error' => 'NIS / id atau nominal tidak valid'];
                continue;
            }

            $custid = resolveCustIdForTagihanRow($pdo, $nis, $custidIn);
            if ($custid <= 0) {
                $errors[] = ['nis' => $nis, 'error' => 'NIS / CUSTID tidak ditemukan di scctcust'];
                continue;
            }

            $furutan = $nextUrutGlobal;
            $billcd = buildTagihanBillCd($thn_akademik, $furutan, $billcdMode);

            try {
                $stmtInsert->execute([
                    ':CUSTID' => $custid,
                    ':BILLCD' => $billcd,
                    ':BILLAC' => $periode,
                    ':BILLNM' => $billnm !== '' ? $billnm : $kode_akun,
                    ':BILLAM' => $nominal,
                    ':BTA'    => $thn_akademik,
                    ':FURUTAN' => $furutan,
                ]);
                $stmtBillAa->execute([':c' => $custid, ':b' => $billcd]);
                $billAa = (int) ($stmtBillAa->fetchColumn() ?: 0);
                $stmtInsertDetail->execute([
                    ':AA' => $billAa,
                    ':KodePost' => trim((string) $kode_akun),
                    ':BILLAM' => $nominal,
                    ':CUST_VAL' => $custid,
                    ':FID' => null,
                    ':tahun' => date('Y'),
                    ':periode' => date('m'),
                    ':BILLCD' => $billcd,
                ]);
                $nextUrutGlobal++;
                $inserted++;
            } catch (Throwable $e) {
                $errors[] = ['nis' => $nis, 'error' => $e->getMessage()];
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 500, 'message' => 'Gagal menyimpan tagihan: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return [
        'inserted' => $inserted,
        'skipped'  => $skipped,
        'errors'   => $errors,
    ];
}

/**
 * Parse tanggal filter Y-m-d; null jika tidak valid (fallback ke predikat DATE() di caller).
 */
function penerimaanParseYmd(string $ymd): ?DateTimeImmutable
{
    $ymd = trim($ymd);
    if ($ymd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
        return null;
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);

    return $d instanceof DateTimeImmutable ? $d : null;
}

/**
 * Bangun WHERE/params untuk query penerimaan (dipakai getDataPenerimaan & getKartuSiswaPenerimaan).
 *
 * @return array{
 *   whereBill: list<string>,
 *   paramsBill: array<string, mixed>,
 *   whereCust: list<string>,
 *   paramsCust: array<string, mixed>,
 *   sekolah: string,
 *   tgl_dari: string, tgl_sampai: string, thn_angkatan: string, thn_akademik: string, kelas_id: string,
 *   nama_tagihan: string, nis: string, nama: string, cari: string, fidbank: string,
 *   periode_mulai: string, periode_akhir: string
 * }
 */
function penerimaanBuildPenerimaanFiltersFromReq(array $req): array
{
    $tglDari = trim((string) ($req['tgl_dari'] ?? ''));
    $tglSampai = trim((string) ($req['tgl_sampai'] ?? ''));
    $thnAngkatan = trim((string) ($req['thn_angkatan'] ?? ''));
    $thnAkademik = trim((string) ($req['thn_akademik'] ?? ''));
    $kelasId = trim((string) ($req['kelas_id'] ?? ''));
    $namaTagihan = trim((string) ($req['nama_tagihan'] ?? $req['tagihan'] ?? ''));
    $nis = trim((string) ($req['nis'] ?? ''));
    $nama = trim((string) ($req['nama'] ?? ''));
    $cari = trim((string) ($req['cari'] ?? ''));
    $fidbank = trim((string) ($req['fidbank'] ?? ''));
    $sekolah = trim((string) ($req['sekolah'] ?? ''));
    $periodeMulai = trim((string) ($req['periode_mulai'] ?? ''));
    $periodeAkhir = trim((string) ($req['periode_akhir'] ?? ''));

    $thnAngkatanBase = trim((string) preg_replace('/\s*-\s*.*/', '', $thnAngkatan));

    $whereBill = [
        'b.FSTSBolehBayar = 1',
        "(b.PAIDST = '1' OR b.PAIDST = 1 OR TRIM(CAST(b.PAIDST AS CHAR)) = '1')",
        'b.PAIDDT IS NOT NULL'
    ];
    $paramsBill = [];

    $dDari = penerimaanParseYmd($tglDari);
    $dSampai = penerimaanParseYmd($tglSampai);
    if ($tglDari !== '' && $tglSampai !== '' && $dDari && $dSampai) {
        $whereBill[] = 'b.PAIDDT >= :p_paid_start AND b.PAIDDT < :p_paid_end_excl';
        $paramsBill[':p_paid_start'] = $dDari->format('Y-m-d H:i:s');
        $paramsBill[':p_paid_end_excl'] = $dSampai->modify('+1 day')->format('Y-m-d H:i:s');
    } elseif ($tglDari !== '' && $dDari) {
        $whereBill[] = 'b.PAIDDT >= :p_paid_start';
        $paramsBill[':p_paid_start'] = $dDari->format('Y-m-d H:i:s');
    } elseif ($tglSampai !== '' && $dSampai) {
        $whereBill[] = 'b.PAIDDT < :p_paid_end_excl';
        $paramsBill[':p_paid_end_excl'] = $dSampai->modify('+1 day')->format('Y-m-d H:i:s');
    } elseif ($tglDari !== '' && $tglSampai !== '') {
        $whereBill[] = 'DATE(b.PAIDDT) BETWEEN :p_tgl_dari AND :p_tgl_sampai';
        $paramsBill[':p_tgl_dari'] = $tglDari;
        $paramsBill[':p_tgl_sampai'] = $tglSampai;
    } elseif ($tglDari !== '') {
        $whereBill[] = 'DATE(b.PAIDDT) >= :p_tgl_dari';
        $paramsBill[':p_tgl_dari'] = $tglDari;
    } elseif ($tglSampai !== '') {
        $whereBill[] = 'DATE(b.PAIDDT) <= :p_tgl_sampai';
        $paramsBill[':p_tgl_sampai'] = $tglSampai;
    }

    $pMulai = penerimaanParseYmd($periodeMulai);
    $pAkhir = penerimaanParseYmd($periodeAkhir);
    if ($periodeMulai !== '' && $periodeAkhir !== '' && $pMulai && $pAkhir) {
        $whereBill[] = 'b.FTGLTagihan >= :pm_start AND b.FTGLTagihan < :pm_end_excl';
        $paramsBill[':pm_start'] = $pMulai->format('Y-m-d H:i:s');
        $paramsBill[':pm_end_excl'] = $pAkhir->modify('+1 day')->format('Y-m-d H:i:s');
    } elseif ($periodeMulai !== '' && $pMulai) {
        $whereBill[] = 'b.FTGLTagihan >= :pm_start';
        $paramsBill[':pm_start'] = $pMulai->format('Y-m-d H:i:s');
    } elseif ($periodeAkhir !== '' && $pAkhir) {
        $whereBill[] = 'b.FTGLTagihan < :pm_end_excl';
        $paramsBill[':pm_end_excl'] = $pAkhir->modify('+1 day')->format('Y-m-d H:i:s');
    } elseif ($periodeMulai !== '' && $periodeAkhir !== '') {
        $whereBill[] = 'DATE(b.FTGLTagihan) BETWEEN :pm_mulai AND :pm_akhir';
        $paramsBill[':pm_mulai'] = $periodeMulai;
        $paramsBill[':pm_akhir'] = $periodeAkhir;
    } elseif ($periodeMulai !== '') {
        $whereBill[] = 'DATE(b.FTGLTagihan) >= :pm_mulai';
        $paramsBill[':pm_mulai'] = $periodeMulai;
    } elseif ($periodeAkhir !== '') {
        $whereBill[] = 'DATE(b.FTGLTagihan) <= :pm_akhir';
        $paramsBill[':pm_akhir'] = $periodeAkhir;
    }

    if ($thnAkademik !== '') {
        $whereBill[] = '(
            UPPER(TRIM(b.BTA)) = UPPER(TRIM(:bta))
            OR UPPER(TRIM(b.BTA)) LIKE CONCAT(UPPER(TRIM(:bta_like)), "%")
            OR LEFT(TRIM(b.BTA), 4) = LEFT(TRIM(:bta_yr), 4)
        )';
        $paramsBill[':bta'] = $thnAkademik;
        $paramsBill[':bta_like'] = $thnAkademik;
        $paramsBill[':bta_yr'] = $thnAkademik;
    }

    if ($namaTagihan !== '') {
        $whereBill[] = 'UPPER(TRIM(b.BILLNM)) = UPPER(TRIM(:billnm))';
        $paramsBill[':billnm'] = $namaTagihan;
    }

    if ($fidbank !== '') {
        $whereBill[] = 'TRIM(CAST(b.FIDBANK AS CHAR)) = :fidbank';
        $paramsBill[':fidbank'] = $fidbank;
    }

    $whereCust = [];
    $paramsCust = [];

    if ($thnAngkatan !== '') {
        $whereCust[] = '(TRIM(c.DESC04) = :thn_ang OR TRIM(c.DESC04) = :thn_ang_base)';
        $paramsCust[':thn_ang'] = $thnAngkatan;
        $paramsCust[':thn_ang_base'] = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thnAngkatan;
    }

    if ($kelasId !== '') {
        $whereCust[] = 'TRIM(c.CODE03) = :kelas_id';
        $paramsCust[':kelas_id'] = $kelasId;
    }

    if ($nis !== '') {
        $whereCust[] = 'TRIM(c.NOCUST) LIKE :nis_like';
        $paramsCust[':nis_like'] = '%' . $nis . '%';
    }

    if ($nama !== '') {
        $whereCust[] = 'TRIM(c.NMCUST) LIKE :nama_like';
        $paramsCust[':nama_like'] = '%' . $nama . '%';
    }

    if ($cari !== '') {
        $cLike = '%' . $cari . '%';
        $whereCust[] = '(
            TRIM(c.NOCUST) LIKE :dp_c1 
            OR TRIM(c.NMCUST) LIKE :dp_c2 
            OR TRIM(b.BILLNM) LIKE :dp_c3
        )';
        $paramsCust[':dp_c1'] = $cLike;
        $paramsCust[':dp_c2'] = $cLike;
        $paramsCust[':dp_c3'] = $cLike;
    }

    if ($sekolah !== '') {
        $whereCust[] = "(COALESCE(NULLIF(TRIM(mk.unit), ''), TRIM(c.CODE02)) LIKE :sekolah)";
        $paramsCust[':sekolah'] = '%' . $sekolah . '%';
    }

    return [
        'whereBill'     => $whereBill,
        'paramsBill'    => $paramsBill,
        'whereCust'     => $whereCust,
        'paramsCust'    => $paramsCust,
        'sekolah'       => $sekolah,
        'tgl_dari'      => $tglDari,
        'tgl_sampai'    => $tglSampai,
        'thn_angkatan'  => $thnAngkatan,
        'thn_akademik'  => $thnAkademik,
        'kelas_id'      => $kelasId,
        'nama_tagihan'  => $namaTagihan,
        'nis'           => $nis,
        'nama'          => $nama,
        'cari'          => $cari,
        'fidbank'       => $fidbank,
        'periode_mulai' => $periodeMulai,
        'periode_akhir' => $periodeAkhir,
    ];
}

/**
 * Data penerimaan (tagihan lunas) untuk halaman Data Penerimaan.
 * Pagination: LIMIT/OFFSET; urutan: AA DESC bila kolom AA ada di scctbill, else PAIDDT DESC.
 * include_total=0: tanpa COUNT(*) (hemat waktu pada dataset besar); ambil limit+1 baris agar client tahu ada halaman berikutnya.
 * include_total=1 (default): COUNT + SELECT seperti biasa (untuk laporan yang butuh total pasti).
 * Filter tanggal memakai rentang datetime pada kolom (bukan DATE(kolom)) agar indeks PAIDDT/FTGLTagihan bisa dipakai.
 * Indeks disarankan: (FSTSBolehBayar, PAIDST, AA DESC) jika AA ada; else (FSTSBolehBayar, PAIDST, PAIDDT DESC).
 */
function getDataPenerimaan(array $req): array
{
    $tWall0 = microtime(true);
    $pdo = dbConnectPdo();

    $tAa0 = microtime(true);
    $useAaOrder = scctbillHasAaColumn($pdo);
    $tAaMs = round((microtime(true) - $tAa0) * 1000, 2);

    $includeTotal = (int) ($req['include_total'] ?? 1) !== 0;
    $forPdf = !empty($req['pdf_export']) || !empty($req['for_pdf']);
    $maxCap = $forPdf ? 8000 : 200;
    $pageLimit = min($maxCap, max(1, (int) ($req['limit'] ?? 25)));
    $offset = max(0, (int) ($req['offset'] ?? 0));
    $sqlLimit = $includeTotal ? $pageLimit : min($maxCap, $pageLimit + 1);

    $fb = penerimaanBuildPenerimaanFiltersFromReq($req);
    $whereBill = $fb['whereBill'];
    $paramsBill = $fb['paramsBill'];
    $whereCust = $fb['whereCust'];
    $paramsCust = $fb['paramsCust'];
    $sekolah = $fb['sekolah'];
    $tglDari = $fb['tgl_dari'];
    $tglSampai = $fb['tgl_sampai'];
    $thnAngkatan = $fb['thn_angkatan'];
    $thnAkademik = $fb['thn_akademik'];
    $kelasId = $fb['kelas_id'];
    $namaTagihan = $fb['nama_tagihan'];
    $nis = $fb['nis'];
    $nama = $fb['nama'];
    $cari = $fb['cari'];
    $fidbank = $fb['fidbank'];
    $periodeMulai = $fb['periode_mulai'];
    $periodeAkhir = $fb['periode_akhir'];

    $whereAll = array_merge($whereBill, $whereCust);
    $whereSql = implode(' AND ', $whereAll);
    $params = array_merge($paramsBill, $paramsCust);

    $useMkJoin = ($sekolah !== '');

    $orderSql = $useAaOrder
        ? 'b.AA DESC, b.CUSTID DESC, b.BILLCD DESC'
        : 'b.PAIDDT DESC, b.BILLCD ASC';

    $sqlMetode = "
        COALESCE(
            CASE TRIM(CAST(b.FIDBANK AS CHAR))
                WHEN '1140000' THEN 'Manual CASH'
                WHEN '1140001' THEN 'Manual BMI'
                WHEN '1140002' THEN 'Manual SALDO'
                WHEN '1140003' THEN 'Transfer Bank Lain'
                WHEN '1200001' THEN 'Loket Manual - Beasiswa'
                WHEN '1200002' THEN 'Loket Manual - Potongan'
            END,
            NULLIF(TRIM(CAST(b.FIDBANK AS CHAR)), ''),
            '-'
        )
    ";

    $aaSelect = $useAaOrder ? ', b.AA AS aa' : '';

    if ($useMkJoin) {
        $sqlBase = "
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        LEFT JOIN mst_kelas mk ON CAST(mk.id AS CHAR) = TRIM(c.CODE03)
        WHERE {$whereSql}
    ";
        $sqlSelectList = "
            b.CUSTID AS custid,
            b.BILLCD AS billcd,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NMCUST) AS nama,
            COALESCE(NULLIF(TRIM(mk.unit), ''), TRIM(c.CODE02), '') AS unit,
            COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC02), '') AS kelas,
            TRIM(b.BILLNM) AS nama_tagihan,
            b.BILLAM AS tagihan,
            {$sqlMetode} AS metode,
            b.PAIDDT AS paiddt,
            TRIM(b.BTA) AS tahun_aka{$aaSelect}
    ";
    } else {
        $sqlBase = "
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        WHERE {$whereSql}
    ";
        $sqlSelectList = "
            b.CUSTID AS custid,
            b.BILLCD AS billcd,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NMCUST) AS nama,
            TRIM(c.CODE02) AS unit,
            TRIM(c.DESC02) AS kelas,
            TRIM(b.BILLNM) AS nama_tagihan,
            b.BILLAM AS tagihan,
            {$sqlMetode} AS metode,
            b.PAIDDT AS paiddt,
            TRIM(b.BTA) AS tahun_aka{$aaSelect}
    ";
    }

    $rows = [];
    $total = 0;
    $tCountMs = 0.0;

    if ($includeTotal) {
        $tCount0 = microtime(true);
        if (empty($whereCust)) {
            $whereBillSql = implode(' AND ', $whereBill);
            $sqlCount = "SELECT COUNT(*) AS total FROM scctbill b WHERE {$whereBillSql}";
            $stc = $pdo->prepare($sqlCount);
            foreach ($paramsBill as $k => $v) {
                $stc->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stc->execute();
            $total = (int) ($stc->fetchColumn() ?: 0);
        } else {
            $sqlCount = "SELECT COUNT(*) AS total {$sqlBase}";
            $stc = $pdo->prepare($sqlCount);
            foreach ($params as $k => $v) {
                $stc->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stc->execute();
            $total = (int) ($stc->fetchColumn() ?: 0);
        }
        $tCountMs = round((microtime(true) - $tCount0) * 1000, 2);
    }

    $sql = "SELECT {$sqlSelectList} {$sqlBase} ORDER BY {$orderSql} LIMIT :limit OFFSET :offset";

    $tSel0 = microtime(true);
    $selectError = null;
    try {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $sqlLimit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $rows = [];
        $selectError = $e->getMessage();
    }
    $tSelectMs = round((microtime(true) - $tSel0) * 1000, 2);

    foreach ($rows as &$row) {
        $row['tagihan'] = (int) ($row['tagihan'] ?? 0);
    }
    unset($row);

    $wallMs = round((microtime(true) - $tWall0) * 1000, 2);
    penerimaanPerfLog([
        'scope'              => 'getDataPenerimaan',
        'wall_ms'            => $wallMs,
        't_aa_column_ms'     => $tAaMs,
        't_count_ms'         => $tCountMs,
        't_select_ms'        => $tSelectMs,
        'use_aa_order'       => $useAaOrder,
        'use_mst_kelas_join' => $useMkJoin,
        'include_total'      => $includeTotal,
        'page_limit'         => $pageLimit,
        'sql_limit'          => $sqlLimit,
        'offset'             => $offset,
        'rows_returned'      => count($rows),
        'has_cust_filters'   => !empty($whereCust),
        'filter_flags'       => [
            'tgl_dari'       => $tglDari !== '',
            'tgl_sampai'     => $tglSampai !== '',
            'thn_akademik'   => $thnAkademik !== '',
            'nama_tagihan'   => $namaTagihan !== '',
            'fidbank'        => $fidbank !== '',
            'periode_tagihan'=> $periodeMulai !== '' || $periodeAkhir !== '',
            'thn_angkatan'   => $thnAngkatan !== '',
            'kelas_id'       => $kelasId !== '',
            'nis'            => $nis !== '',
            'nama'           => $nama !== '',
            'cari'           => $cari !== '',
            'sekolah'        => $sekolah !== '',
        ],
        'hint'               => 'Jika t_select_ms besar: jalankan indeks di database/sql/scctbill_index_penerimaan.sql; OFFSET besar memperlambat — pertimbangkan keyset pagination.',
        'select_error'       => $selectError,
    ]);

    $out = [
        'rows' => $rows,
        'meta' => [
            'sort_by_aa' => $useAaOrder,
            'exact_total' => $includeTotal,
        ],
    ];
    if ($includeTotal) {
        $out['total'] = $total;
    }

    return $out;
}

/**
 * Kartu siswa dari konteks Data Penerimaan: data siswa (scctcust GENUS/GENUS1) + baris penerimaan (lunas) per filter + custid terpilih.
 * No VA = 7510050 + digit NOCUST.
 */
function getKartuSiswaPenerimaan(array $req): array
{
    $custidsRaw = $req['custids'] ?? [];
    if (!is_array($custidsRaw)) {
        $custidsRaw = [];
    }
    $custids = [];
    foreach ($custidsRaw as $v) {
        $n = (int) $v;
        if ($n > 0) {
            $custids[$n] = true;
        }
    }
    $custids = array_keys($custids);
    sort($custids, SORT_NUMERIC);
    if ($custids === []) {
        return ['error' => 'Pilih minimal satu siswa.', 'cards' => []];
    }
    if (count($custids) > 40) {
        return ['error' => 'Maksimal 40 siswa per cetak.', 'cards' => []];
    }

    $pdo = dbConnectPdo();
    $useAaOrder = scctbillHasAaColumn($pdo);

    $fb = penerimaanBuildPenerimaanFiltersFromReq($req);
    $whereBill = $fb['whereBill'];
    $paramsBill = $fb['paramsBill'];
    $whereCust = $fb['whereCust'];
    $paramsCust = $fb['paramsCust'];
    $sekolah = $fb['sekolah'];

    $inPh = [];
    foreach ($custids as $i => $cid) {
        $k = ':kartu_cust_' . $i;
        $inPh[] = $k;
        $paramsCust[$k] = $cid;
    }
    $whereCust[] = 'b.CUSTID IN (' . implode(',', $inPh) . ')';

    $whereAll = array_merge($whereBill, $whereCust);
    $whereSql = implode(' AND ', $whereAll);
    $params = array_merge($paramsBill, $paramsCust);

    $useMkJoin = ($sekolah !== '');
    $orderSql = $useAaOrder
        ? 'b.AA DESC, b.CUSTID DESC, b.BILLCD DESC'
        : 'b.PAIDDT DESC, b.BILLCD ASC';

    $sqlMetode = "
        COALESCE(
            CASE TRIM(CAST(b.FIDBANK AS CHAR))
                WHEN '1140000' THEN 'Manual CASH'
                WHEN '1140001' THEN 'Manual BMI'
                WHEN '1140002' THEN 'Manual SALDO'
                WHEN '1140003' THEN 'Transfer Bank Lain'
                WHEN '1200001' THEN 'Loket Manual - Beasiswa'
                WHEN '1200002' THEN 'Loket Manual - Potongan'
            END,
            NULLIF(TRIM(CAST(b.FIDBANK AS CHAR)), ''),
            '-'
        )
    ";

    $aaSelect = $useAaOrder ? ', b.AA AS aa' : '';

    if ($useMkJoin) {
        $sqlBase = "
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        LEFT JOIN mst_kelas mk ON CAST(mk.id AS CHAR) = TRIM(c.CODE03)
        WHERE {$whereSql}
    ";
        $sqlSelectList = "
            b.CUSTID AS custid,
            b.BILLCD AS billcd,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NMCUST) AS nama,
            COALESCE(NULLIF(TRIM(mk.unit), ''), TRIM(c.CODE02), '') AS unit,
            COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC02), '') AS kelas,
            TRIM(b.BILLNM) AS nama_tagihan,
            b.BILLAM AS tagihan,
            {$sqlMetode} AS metode,
            b.PAIDDT AS paiddt,
            TRIM(b.BTA) AS tahun_aka{$aaSelect}
    ";
    } else {
        $sqlBase = "
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        WHERE {$whereSql}
    ";
        $sqlSelectList = "
            b.CUSTID AS custid,
            b.BILLCD AS billcd,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NMCUST) AS nama,
            TRIM(c.CODE02) AS unit,
            TRIM(c.DESC02) AS kelas,
            TRIM(b.BILLNM) AS nama_tagihan,
            b.BILLAM AS tagihan,
            {$sqlMetode} AS metode,
            b.PAIDDT AS paiddt,
            TRIM(b.BTA) AS tahun_aka{$aaSelect}
    ";
    }

    $maxRows = 8000;
    $sql = "SELECT {$sqlSelectList} {$sqlBase} ORDER BY {$orderSql} LIMIT " . (int) $maxRows;

    $billRows = [];
    try {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $isInt = str_starts_with((string) $k, ':kartu_cust_');
            $stmt->bindValue($k, $v, $isInt ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $billRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return ['error' => 'Gagal mengambil data penerimaan: ' . $e->getMessage(), 'cards' => []];
    }

    $inS = [];
    $psCust = [];
    foreach ($custids as $i => $cid) {
        $k = ':scust_' . $i;
        $inS[] = $k;
        $psCust[$k] = $cid;
    }
    $sqlCust = "
        SELECT
            CUSTID,
            TRIM(NOCUST) AS NOCUST,
            TRIM(NMCUST) AS NMCUST,
            TRIM(CODE02) AS CODE02,
            TRIM(DESC02) AS DESC02,
            TRIM(DESC03) AS DESC03,
            TRIM(DESC04) AS DESC04,
            TRIM(GENUS) AS GENUS,
            TRIM(GENUS1) AS GENUS1
        FROM scctcust
        WHERE CUSTID IN (" . implode(',', $inS) . ")
    ";
    $siswaById = [];
    try {
        $stc = $pdo->prepare($sqlCust);
        foreach ($psCust as $k => $v) {
            $stc->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stc->execute();
        while ($row = $stc->fetch(PDO::FETCH_ASSOC)) {
            $cid = (int) ($row['CUSTID'] ?? 0);
            if ($cid > 0) {
                $siswaById[$cid] = $row;
            }
        }
    } catch (Throwable $e) {
        return ['error' => 'Gagal mengambil data siswa: ' . $e->getMessage(), 'cards' => []];
    }

    $cards = [];
    foreach ($custids as $cid) {
        $s = $siswaById[$cid] ?? null;
        $nocust = trim((string) ($s['NOCUST'] ?? ''));
        $digits = preg_replace('/\D+/', '', $nocust);
        $noVa = '7510050' . ($digits !== '' ? $digits : '0');
        $cards[$cid] = [
            'custid'   => $cid,
            'nis'      => $nocust,
            'no_va'    => $noVa,
            'nama'     => trim((string) ($s['NMCUST'] ?? '')),
            'unit'     => trim((string) ($s['CODE02'] ?? '')),
            'kelas'    => trim((string) ($s['DESC02'] ?? '')),
            'angkatan' => trim((string) ($s['DESC04'] ?? '')),
            'kelompok' => trim((string) ($s['DESC03'] ?? '')),
            'ayah'     => trim((string) ($s['GENUS'] ?? '')),
            'ibu'      => trim((string) ($s['GENUS1'] ?? '')),
            'items'    => [],
            'total'    => 0,
        ];
    }

    foreach ($billRows as $br) {
        $cid = (int) ($br['custid'] ?? 0);
        if ($cid <= 0 || !isset($cards[$cid])) {
            continue;
        }
        $tag = (int) ($br['tagihan'] ?? 0);
        $paiddt = trim((string) ($br['paiddt'] ?? ''));
        $tbayar = '-';
        if ($paiddt !== '') {
            $ts = strtotime($paiddt);
            $tbayar = $ts ? date('d-m-Y H:i', $ts) : $paiddt;
            if ($tbayar === '01-01-1970 07:00') {
                $tbayar = $paiddt;
            }
        }
        $cards[$cid]['items'][] = [
            'tahun_aka'    => trim((string) ($br['tahun_aka'] ?? '')),
            'nama_tagihan' => trim((string) ($br['nama_tagihan'] ?? '')),
            'tagihan'      => $tag,
            'metode'       => trim((string) ($br['metode'] ?? '')),
            'tbayar'       => $tbayar,
            'paiddt'       => $paiddt,
        ];
        $cards[$cid]['total'] += $tag;
        if ($cards[$cid]['nis'] === '' && trim((string) ($br['nis'] ?? '')) !== '') {
            $cards[$cid]['nis'] = trim((string) $br['nis']);
        }
        if ($cards[$cid]['nama'] === '' && trim((string) ($br['nama'] ?? '')) !== '') {
            $cards[$cid]['nama'] = trim((string) $br['nama']);
        }
        if ($cards[$cid]['unit'] === '' && trim((string) ($br['unit'] ?? '')) !== '') {
            $cards[$cid]['unit'] = trim((string) $br['unit']);
        }
        if ($cards[$cid]['kelas'] === '' && trim((string) ($br['kelas'] ?? '')) !== '') {
            $cards[$cid]['kelas'] = trim((string) $br['kelas']);
        }
    }

    $outList = [];
    foreach ($custids as $cid) {
        if (!isset($cards[$cid])) {
            continue;
        }
        if (($cards[$cid]['items'] ?? []) === []) {
            continue;
        }
        $outList[] = $cards[$cid];
    }

    if ($outList === []) {
        return ['error' => 'Tidak ada baris penerimaan (lunas) untuk siswa terpilih pada filter saat ini.', 'cards' => []];
    }

    return ['cards' => $outList];
}

/** True jika kolom scctbill.AA ada (dipakai urutan data penerimaan). Cache file 24 jam agar tidak SHOW COLUMNS tiap request. */
function scctbillHasAaColumn(PDO $pdo): bool
{
    static $mem = null;
    if ($mem !== null) {
        return $mem;
    }
    $cacheFile = __DIR__ . "/cache_scctbill_has_aa.txt";
    if (is_readable($cacheFile) && (time() - (int) filemtime($cacheFile)) < 86400) {
        $v = trim((string) file_get_contents($cacheFile));

        return $mem = ($v === "1");
    }
    try {
        $st = $pdo->query("SHOW COLUMNS FROM scctbill LIKE 'AA'");
        $has = (bool) ($st && $st->fetch(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        $has = false;
    }
    @file_put_contents($cacheFile, $has ? "1" : "0", LOCK_EX);

    return $mem = $has;
}


function getDataPembayaranPerNis(array $req): array
{
    $pdo = dbConnectPdo();
    $detailCustCol = detectScctbillDetailCustColumn($pdo);
    $custids = $req['custids'] ?? [];
    if (!is_array($custids)) {
        $custids = [];
    }

    $custidNums = [];
    foreach ($custids as $v) {
        $n = (int) $v;
        if ($n > 0) {
            $custidNums[] = $n;
        }
    }
    $custidNums = array_values(array_unique($custidNums));

    $where = ['b.FSTSBolehBayar = 1'];
    $params = [];
    if ($custidNums !== []) {
        $inParams = [];
        foreach ($custidNums as $i => $custid) {
            $ph = ':custid_' . $i;
            $inParams[] = $ph;
            $params[$ph] = $custid;
        }
        $where[] = 'b.CUSTID IN (' . implode(', ', $inParams) . ')';
    }

    $tglDari     = trim((string) ($req['tgl_dari'] ?? ''));
    $tglSampai   = trim((string) ($req['tgl_sampai'] ?? ''));
    $thnAngkatan = trim((string) ($req['thn_angkatan'] ?? ''));
    $thnAkademik = trim((string) ($req['thn_akademik'] ?? ''));
    $kelasId     = trim((string) ($req['kelas_id'] ?? ''));
    $namaTagihan = trim((string) ($req['nama_tagihan'] ?? ''));
    $siswa       = trim((string) ($req['siswa'] ?? ''));
    $thnAngkatanBase = trim((string) preg_replace('/\s*-\s*.*/', '', $thnAngkatan));

    if ($tglDari !== '' && $tglSampai !== '') {
        $where[] = 'DATE(b.FTGLTagihan) BETWEEN :tgl_dari AND :tgl_sampai';
        $params[':tgl_dari'] = $tglDari;
        $params[':tgl_sampai'] = $tglSampai;
    } elseif ($tglDari !== '') {
        $where[] = 'DATE(b.FTGLTagihan) >= :tgl_dari';
        $params[':tgl_dari'] = $tglDari;
    } elseif ($tglSampai !== '') {
        $where[] = 'DATE(b.FTGLTagihan) <= :tgl_sampai';
        $params[':tgl_sampai'] = $tglSampai;
    }
    if ($thnAngkatan !== '') {
        $where[] = '(TRIM(c.DESC04) = :thn_ang OR TRIM(c.DESC04) = :thn_ang_base)';
        $params[':thn_ang'] = $thnAngkatan;
        $params[':thn_ang_base'] = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thnAngkatan;
    }
    if ($thnAkademik !== '') {
        $where[] = '(
            UPPER(TRIM(b.BTA)) = UPPER(TRIM(:bta))
            OR UPPER(TRIM(b.BTA)) LIKE CONCAT(UPPER(TRIM(:bta_like)), "%")
            OR LEFT(TRIM(b.BTA), 4) = LEFT(TRIM(:bta_yr), 4)
        )';
        $params[':bta'] = $thnAkademik;
        $params[':bta_like'] = $thnAkademik;
        $params[':bta_yr'] = $thnAkademik;
    }
    if ($kelasId !== '') {
        $where[] = 'TRIM(c.CODE03) = :kelas_id';
        $params[':kelas_id'] = $kelasId;
    }
    if ($namaTagihan !== '') {
        $where[] = 'UPPER(TRIM(b.BILLNM)) = UPPER(TRIM(:billnm))';
        $params[':billnm'] = $namaTagihan;
    }
    if ($siswa !== '') {
        $where[] = '(TRIM(c.NOCUST) LIKE :sw OR TRIM(c.NMCUST) LIKE :sw2)';
        $params[':sw'] = '%' . $siswa . '%';
        $params[':sw2'] = '%' . $siswa . '%';
    }

    $whereSql = implode(' AND ', $where);
    $sql = "
        WITH base AS (
            SELECT
                b.CUSTID,
                b.BILLCD,
                TRIM(b.BILLAC) AS billac,
                CAST(COALESCE(b.BILLAM, 0) AS SIGNED) AS billam,
                TRIM(c.DESC04) AS tahun_masuk,
                COALESCE(NULLIF(TRIM(mk.unit), ''), TRIM(c.CODE02), '') AS unit,
                COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC02), '') AS kelas,
                COALESCE(NULLIF(TRIM(mk.kelompok), ''), TRIM(c.DESC03), '') AS kelompok,
                TRIM(c.NOCUST) AS nis,
                TRIM(c.NMCUST) AS nama
            FROM scctbill b
            INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
            LEFT JOIN mst_kelas mk ON CAST(mk.id AS CHAR) = TRIM(c.CODE03)
            WHERE {$whereSql}
        )
        SELECT * FROM (
            SELECT
                base.CUSTID AS custid,
                base.tahun_masuk,
                base.unit,
                base.kelas,
                base.kelompok,
                base.nis,
                base.nama,
                base.billac,
                COALESCE(NULLIF(TRIM(ua.NamaAkun), ''), 'LAINNYA') AS akun,
                CAST(COALESCE(d.BILLAM, 0) AS SIGNED) AS nominal
            FROM base
            INNER JOIN scctbill_detail d ON d.BILLCD = base.BILLCD AND d.{$detailCustCol} = base.CUSTID
            LEFT JOIN u_akun ua ON (TRIM(ua.KodeAkun) = TRIM(d.KodePost) OR TRIM(ua.NoRek) = TRIM(d.KodePost))

            UNION ALL

            SELECT
                base.CUSTID AS custid,
                base.tahun_masuk,
                base.unit,
                base.kelas,
                base.kelompok,
                base.nis,
                base.nama,
                base.billac,
                COALESCE(NULLIF(TRIM(uaBillac.NamaAkun), ''), 'TAGIHAN') AS akun,
                base.billam AS nominal
            FROM base
            LEFT JOIN u_akun uaBillac ON TRIM(uaBillac.NoRek) = TRIM(base.billac)
            LEFT JOIN scctbill_detail d0 ON d0.BILLCD = base.BILLCD AND d0.{$detailCustCol} = base.CUSTID
            WHERE d0.BILLCD IS NULL
        ) x
        ORDER BY x.custid ASC, x.billac ASC, x.akun ASC
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        if (strpos($k, ':custid_') === 0) {
            $stmt->bindValue($k, (int) $v, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($k, (string) $v, PDO::PARAM_STR);
        }
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['nominal'] = (int) ($row['nominal'] ?? 0);
    }
    unset($row);

    return ['rows' => $rows];
}

/**
 * WHERE + params untuk tagihan belum lunas (Hapus Tagihan) — tanggal pembuatan = FTGLTagihan.
 *
 * @return array{0: list<string>, 1: array<string, mixed>}
 */
function hapusTagihanBuildWhereFromReq(array $req): array
{
    $where = [
        'b.FSTSBolehBayar = 1',
        "(b.PAIDST = '0' OR b.PAIDST = 0 OR TRIM(CAST(b.PAIDST AS CHAR)) = '0')",
    ];
    $params = [];

    $tglDari = trim((string) ($req['tgl_dari'] ?? ''));
    $tglSampai = trim((string) ($req['tgl_sampai'] ?? ''));
    $thnAngkatan = trim((string) ($req['thn_angkatan'] ?? ''));
    $thnAkademik = trim((string) ($req['thn_akademik'] ?? ''));
    $kelasId = trim((string) ($req['kelas_id'] ?? ''));
    $namaTagihan = trim((string) ($req['nama_tagihan'] ?? ''));
    $siswa = trim((string) ($req['siswa'] ?? ''));
    $cari = trim((string) ($req['cari'] ?? ''));
    $thnAngkatanBase = trim((string) preg_replace('/\s*-\s*.*/', '', $thnAngkatan));

    if ($tglDari !== '' && $tglSampai !== '') {
        $where[] = 'DATE(b.FTGLTagihan) BETWEEN :ht_tgl_dari AND :ht_tgl_sampai';
        $params[':ht_tgl_dari'] = $tglDari;
        $params[':ht_tgl_sampai'] = $tglSampai;
    } elseif ($tglDari !== '') {
        $where[] = 'DATE(b.FTGLTagihan) >= :ht_tgl_dari';
        $params[':ht_tgl_dari'] = $tglDari;
    } elseif ($tglSampai !== '') {
        $where[] = 'DATE(b.FTGLTagihan) <= :ht_tgl_sampai';
        $params[':ht_tgl_sampai'] = $tglSampai;
    }

    if ($thnAngkatan !== '') {
        $where[] = '(TRIM(c.DESC04) = :ht_thn_ang OR TRIM(c.DESC04) = :ht_thn_ang_base)';
        $params[':ht_thn_ang'] = $thnAngkatan;
        $params[':ht_thn_ang_base'] = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thnAngkatan;
    }
    if ($thnAkademik !== '') {
        $where[] = '(
            UPPER(TRIM(b.BTA)) = UPPER(TRIM(:ht_bta))
            OR UPPER(TRIM(b.BTA)) LIKE CONCAT(UPPER(TRIM(:ht_bta_like)), "%")
            OR LEFT(TRIM(b.BTA), 4) = LEFT(TRIM(:ht_bta_yr), 4)
        )';
        $params[':ht_bta'] = $thnAkademik;
        $params[':ht_bta_like'] = $thnAkademik;
        $params[':ht_bta_yr'] = $thnAkademik;
    }
    if ($kelasId !== '') {
        $where[] = 'TRIM(c.CODE03) = :ht_kelas_id';
        $params[':ht_kelas_id'] = $kelasId;
    }
    if ($namaTagihan !== '') {
        $where[] = 'UPPER(TRIM(b.BILLNM)) = UPPER(TRIM(:ht_billnm))';
        $params[':ht_billnm'] = $namaTagihan;
    }

    $kw = $cari !== '' ? $cari : $siswa;
    if ($kw !== '') {
        $where[] = '(
            TRIM(c.NOCUST) LIKE :ht_sw
            OR TRIM(c.NMCUST) LIKE :ht_sw2
            OR TRIM(b.BILLNM) LIKE :ht_sw3
        )';
        $like = '%' . $kw . '%';
        $params[':ht_sw'] = $like;
        $params[':ht_sw2'] = $like;
        $params[':ht_sw3'] = $like;
    }

    return [$where, $params];
}

/**
 * Daftar tagihan belum lunas untuk dicentang lalu dihapus (scctbill + scctbill_detail).
 */
function getHapusTagihanRows(array $req): array
{
    $tWall0 = microtime(true);
    $pdo = dbConnectPdo();
    $useAaOrder = scctbillHasAaColumn($pdo);

    $limit = min(100, max(1, (int) ($req['limit'] ?? 25)));
    $offset = max(0, (int) ($req['offset'] ?? 0));
    $fetchLimit = $limit + 1;

    [$where, $params] = hapusTagihanBuildWhereFromReq($req);
    $whereSql = implode(' AND ', $where);
    $orderSql = $useAaOrder
        ? 'b.AA DESC, b.CUSTID DESC, b.BILLCD DESC'
        : 'b.FTGLTagihan DESC, b.CUSTID DESC, b.BILLCD DESC';

    $sql = "
        SELECT
            b.CUSTID AS custid,
            b.BILLCD AS billcd,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NMCUST) AS nama,
            COALESCE(NULLIF(TRIM(mk.unit), ''), TRIM(c.CODE02), '') AS unit,
            COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC02), '') AS kelas,
            TRIM(b.BILLNM) AS nama_tagihan,
            CAST(COALESCE(b.BILLAM, 0) AS SIGNED) AS tagihan,
            TRIM(b.BTA) AS tahun_aka
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        LEFT JOIN mst_kelas mk ON CAST(mk.id AS CHAR) = TRIM(c.CODE03)
        WHERE {$whereSql}
        ORDER BY {$orderSql}
        LIMIT " . (int) $fetchLimit . " OFFSET " . (int) $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, (string) $v, PDO::PARAM_STR);
    }
    $stmt->execute();
    $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasMore = count($raw) > $limit;
    if ($hasMore) {
        $raw = array_slice($raw, 0, $limit);
    }

    $rows = [];
    foreach ($raw as $r) {
        $rows[] = [
            'custid' => (int) ($r['custid'] ?? 0),
            'billcd' => trim((string) ($r['billcd'] ?? '')),
            'nis' => trim((string) ($r['nis'] ?? '')),
            'nama' => trim((string) ($r['nama'] ?? '')),
            'unit' => trim((string) ($r['unit'] ?? '')),
            'kelas' => trim((string) ($r['kelas'] ?? '')),
            'nama_tagihan' => trim((string) ($r['nama_tagihan'] ?? '')),
            'tagihan' => (int) ($r['tagihan'] ?? 0),
            'tahun_aka' => trim((string) ($r['tahun_aka'] ?? '')),
        ];
    }

    return [
        'rows' => $rows,
        'meta' => [
            't_select_ms' => round((microtime(true) - $tWall0) * 1000, 2),
            'has_more' => $hasMore,
        ],
    ];
}

/**
 * Daftar Cek Pelunasan:
 * menampilkan semua tagihan (lunas/belum) dengan syarat FSTSBolehBayar = 1.
 */
function getCekPelunasanRows(array $req): array
{
    $tWall0 = microtime(true);
    $pdo = dbConnectPdo();
    $useAaOrder = scctbillHasAaColumn($pdo);

    $limit = min(100, max(1, (int) ($req['limit'] ?? 25)));
    $offset = max(0, (int) ($req['offset'] ?? 0));
    $fetchLimit = $limit + 1;

    $thnAkademik = trim((string) ($req['thn_akademik'] ?? ''));
    $kelasId = trim((string) ($req['kelas_id'] ?? ''));
    $nis = trim((string) ($req['nis'] ?? ''));
    $thnAngkatan = trim((string) ($req['thn_angkatan'] ?? ''));
    $nama = trim((string) ($req['nama'] ?? ''));
    $namaTagihan = trim((string) ($req['nama_tagihan'] ?? ''));
    $cari = trim((string) ($req['cari'] ?? ''));
    $thnAngkatanBase = trim((string) preg_replace('/\s*-\s*.*/', '', $thnAngkatan));

    $where = ['b.FSTSBolehBayar = 1'];
    $params = [];

    if ($thnAkademik !== '') {
        $where[] = '(
            UPPER(TRIM(b.BTA)) = UPPER(TRIM(:cp_bta))
            OR UPPER(TRIM(b.BTA)) LIKE CONCAT(UPPER(TRIM(:cp_bta_like)), "%")
            OR LEFT(TRIM(b.BTA), 4) = LEFT(TRIM(:cp_bta_yr), 4)
        )';
        $params[':cp_bta'] = $thnAkademik;
        $params[':cp_bta_like'] = $thnAkademik;
        $params[':cp_bta_yr'] = $thnAkademik;
    }
    if ($kelasId !== '') {
        $where[] = 'TRIM(c.CODE03) = :cp_kelas_id';
        $params[':cp_kelas_id'] = $kelasId;
    }
    if ($nis !== '') {
        $where[] = 'TRIM(c.NOCUST) LIKE :cp_nis';
        $params[':cp_nis'] = '%' . $nis . '%';
    }
    if ($thnAngkatan !== '') {
        $where[] = '(TRIM(c.DESC04) = :cp_thn_ang OR TRIM(c.DESC04) = :cp_thn_ang_base)';
        $params[':cp_thn_ang'] = $thnAngkatan;
        $params[':cp_thn_ang_base'] = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thnAngkatan;
    }
    if ($nama !== '') {
        $where[] = 'TRIM(c.NMCUST) LIKE :cp_nama';
        $params[':cp_nama'] = '%' . $nama . '%';
    }
    if ($namaTagihan !== '') {
        $where[] = 'UPPER(TRIM(b.BILLNM)) = UPPER(TRIM(:cp_billnm))';
        $params[':cp_billnm'] = $namaTagihan;
    }
    if ($cari !== '') {
        $where[] = '(
            TRIM(c.NOCUST) LIKE :cp_sw1
            OR TRIM(c.NUM2ND) LIKE :cp_sw2
            OR TRIM(c.NMCUST) LIKE :cp_sw3
            OR TRIM(b.BILLNM) LIKE :cp_sw4
        )';
        $like = '%' . $cari . '%';
        $params[':cp_sw1'] = $like;
        $params[':cp_sw2'] = $like;
        $params[':cp_sw3'] = $like;
        $params[':cp_sw4'] = $like;
    }

    $whereSql = implode(' AND ', $where);
    $orderSql = $useAaOrder
        ? 'b.AA DESC, b.CUSTID DESC, b.BILLCD DESC'
        : 'b.FTGLTagihan DESC, b.CUSTID DESC, b.BILLCD DESC';

    $sql = "
        SELECT
            b.CUSTID AS custid,
            TRIM(b.BTA) AS tahun_pelajaran,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NUM2ND) AS no_pendaftaran,
            TRIM(c.NMCUST) AS nama,
            TRIM(b.BILLNM) AS nama_tagihan,
            CAST(COALESCE(b.BILLAM, 0) AS SIGNED) AS tagihan,
            CASE
                WHEN (b.PAIDST = '1' OR b.PAIDST = 1 OR TRIM(CAST(b.PAIDST AS CHAR)) = '1') THEN 1
                ELSE 0
            END AS lunas
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        WHERE {$whereSql}
        ORDER BY {$orderSql}
        LIMIT " . (int) $fetchLimit . " OFFSET " . (int) $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, (string) $v, PDO::PARAM_STR);
    }
    $stmt->execute();
    $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasMore = count($raw) > $limit;
    if ($hasMore) {
        $raw = array_slice($raw, 0, $limit);
    }

    $rows = [];
    foreach ($raw as $r) {
        $rows[] = [
            'custid' => (int) ($r['custid'] ?? 0),
            'tahun_pelajaran' => trim((string) ($r['tahun_pelajaran'] ?? '')),
            'nis' => trim((string) ($r['nis'] ?? '')),
            'no_pendaftaran' => trim((string) ($r['no_pendaftaran'] ?? '')),
            'nama' => trim((string) ($r['nama'] ?? '')),
            'nama_tagihan' => trim((string) ($r['nama_tagihan'] ?? '')),
            'tagihan' => (int) ($r['tagihan'] ?? 0),
            'lunas' => (int) ($r['lunas'] ?? 0),
        ];
    }

    return [
        'rows' => $rows,
        'meta' => [
            't_select_ms' => round((microtime(true) - $tWall0) * 1000, 2),
            'has_more' => $hasMore,
        ],
    ];
}

/**
 * Kartu siswa (cek pelunasan): tampilkan semua bill siswa terpilih dengan FSTSBolehBayar = 1.
 *
 * @return array{cards: list<array<string, mixed>>, error?: string}
 */
function getCekPelunasanCards(array $req): array
{
    $custidsRaw = $req['custids'] ?? [];
    if (!is_array($custidsRaw)) {
        $custidsRaw = [];
    }
    $custids = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $custidsRaw), static fn ($n) => $n > 0)));
    if ($custids === []) {
        return ['cards' => [], 'error' => 'Pilih minimal satu siswa.'];
    }
    if (count($custids) > 100) {
        return ['cards' => [], 'error' => 'Maksimal 100 siswa per cetak.'];
    }

    $pdo = dbConnectPdo();

    $inPlaceholders = [];
    $params = [];
    foreach ($custids as $i => $cid) {
        $ph = ':cpc_' . $i;
        $inPlaceholders[] = $ph;
        $params[$ph] = $cid;
    }
    $inSql = implode(',', $inPlaceholders);

    $sql = "
        SELECT
            b.CUSTID AS custid,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NMCUST) AS nama,
            COALESCE(NULLIF(TRIM(mk.unit), ''), TRIM(c.CODE02), '') AS unit,
            COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC02), '') AS kelas,
            TRIM(COALESCE(c.DESC03, '')) AS kelompok,
            TRIM(b.BTA) AS tahun_aka,
            TRIM(b.BILLNM) AS nama_tagihan,
            CAST(COALESCE(b.BILLAM, 0) AS SIGNED) AS tagihan,
            CASE
                WHEN (b.PAIDST = '1' OR b.PAIDST = 1 OR TRIM(CAST(b.PAIDST AS CHAR)) = '1') THEN 'Lunas'
                ELSE 'Belum lunas'
            END AS status
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        LEFT JOIN mst_kelas mk ON CAST(mk.id AS CHAR) = TRIM(c.CODE03)
        WHERE b.FSTSBolehBayar = 1
          AND b.CUSTID IN ({$inSql})
        ORDER BY b.CUSTID ASC, b.BTA ASC, b.BILLNM ASC, b.BILLCD ASC
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, (int) $v, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cards = [];
    foreach ($rows as $r) {
        $cid = (int) ($r['custid'] ?? 0);
        if ($cid <= 0) {
            continue;
        }
        if (!isset($cards[$cid])) {
            $cards[$cid] = [
                'custid' => $cid,
                'nis' => trim((string) ($r['nis'] ?? '')),
                'nama' => trim((string) ($r['nama'] ?? '')),
                'unit' => trim((string) ($r['unit'] ?? '')),
                'kelas' => trim((string) ($r['kelas'] ?? '')),
                'kelompok' => trim((string) ($r['kelompok'] ?? '')),
                'items' => [],
            ];
        }
        $cards[$cid]['items'][] = [
            'nama_tagihan' => trim((string) ($r['nama_tagihan'] ?? '')),
            'tahun_aka' => trim((string) ($r['tahun_aka'] ?? '')),
            'tagihan' => (int) ($r['tagihan'] ?? 0),
            'status' => trim((string) ($r['status'] ?? 'Belum lunas')),
        ];
    }

    return ['cards' => array_values($cards)];
}

/**
 * Hapus banyak tagihan belum lunas: detail dulu, lalu header.
 *
 * @return array{deleted: int, failed: list<array{custid: int, billcd: string, message: string}>, error?: string}
 */
function hapusTagihanSiswaBatch(array $req): array
{
    $items = $req['items'] ?? [];
    if (!is_array($items) || $items === []) {
        return ['deleted' => 0, 'failed' => [], 'error' => 'Pilih minimal satu tagihan.'];
    }
    if (count($items) > 300) {
        return ['deleted' => 0, 'failed' => [], 'error' => 'Maksimal 300 tagihan per aksi.'];
    }

    $pdo = dbConnectPdo();
    $deleted = 0;
    $failed = [];
    $seen = [];

    try {
        $pdo->beginTransaction();
        foreach ($items as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $cid = (int) ($raw['custid'] ?? 0);
            $bcd = trim((string) ($raw['billcd'] ?? ''));
            if ($cid <= 0 || $bcd === '') {
                continue;
            }
            $k = $cid . '|' . $bcd;
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;

            $r = hapusTagihanDeleteUnpaidBill($pdo, $cid, $bcd);
            if ($r['ok']) {
                $deleted++;
            } else {
                $failed[] = ['custid' => $cid, 'billcd' => $bcd, 'message' => $r['message']];
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['deleted' => 0, 'failed' => [], 'error' => 'Gagal menghapus: ' . $e->getMessage()];
    }

    return ['deleted' => $deleted, 'failed' => $failed];
}

/**
 * Data Biaya Admin dari scctbill.
 * Nominal biaya admin fixed 2000 per baris (bukan BILLAM).
 * No. Invoice diambil dari TRANSNO.
 */
function getDataBiayaAdminRows(array $req): array
{
    $tWall0 = microtime(true);
    $pdo = dbConnectPdo();

    $limit = min(100, max(1, (int) ($req['limit'] ?? 25)));
    $offset = max(0, (int) ($req['offset'] ?? 0));
    $fetchLimit = $limit + 1;

    $tglDari = trim((string) ($req['tgl_dari'] ?? ''));
    $tglSampai = trim((string) ($req['tgl_sampai'] ?? ''));
    $cari = trim((string) ($req['cari'] ?? ''));
    $includeTotal = isset($req['include_total']) && (string) $req['include_total'] === '1';

    $where = [
        "TRIM(CAST(b.FIDBANK AS CHAR)) IN ('1140000','1140001','1140003','1200001','1200002')",
        "(b.PAIDST = '1' OR b.PAIDST = 1 OR TRIM(CAST(b.PAIDST AS CHAR)) = '1')",
        "b.PAIDDT IS NOT NULL",
    ];
    $params = [];

    $dDari = penerimaanParseYmd($tglDari);
    $dSampai = penerimaanParseYmd($tglSampai);
    if ($tglDari !== '' && $tglSampai !== '' && $dDari && $dSampai) {
        $where[] = 'b.PAIDDT >= :ba_start AND b.PAIDDT < :ba_end_excl';
        $params[':ba_start'] = $dDari->format('Y-m-d H:i:s');
        $params[':ba_end_excl'] = $dSampai->modify('+1 day')->format('Y-m-d H:i:s');
    } elseif ($tglDari !== '' && $dDari) {
        $where[] = 'b.PAIDDT >= :ba_start';
        $params[':ba_start'] = $dDari->format('Y-m-d H:i:s');
    } elseif ($tglSampai !== '' && $dSampai) {
        $where[] = 'b.PAIDDT < :ba_end_excl';
        $params[':ba_end_excl'] = $dSampai->modify('+1 day')->format('Y-m-d H:i:s');
    }

    if ($cari !== '') {
        $where[] = '(
            TRIM(c.NOCUST) LIKE :ba_c1
            OR TRIM(c.NMCUST) LIKE :ba_c2
            OR TRIM(c.CODE02) LIKE :ba_c3
            OR TRIM(COALESCE(b.TRANSNO, \'\')) LIKE :ba_c4
        )';
        $like = '%' . $cari . '%';
        $params[':ba_c1'] = $like;
        $params[':ba_c2'] = $like;
        $params[':ba_c3'] = $like;
        $params[':ba_c4'] = $like;
    }

    $whereSql = implode(' AND ', $where);

    $totalFiltered = 0;
    if ($includeTotal) {
        $sqlCount = "
            SELECT COUNT(*) AS total_filtered
            FROM scctbill b
            INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
            WHERE {$whereSql}
        ";
        $stc = $pdo->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $stc->bindValue($k, (string) $v, PDO::PARAM_STR);
        }
        $stc->execute();
        $totalFiltered = (int) ($stc->fetchColumn() ?: 0);
    }

    $sql = "
        SELECT
            TRIM(c.CODE02) AS sekolah,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NMCUST) AS nama,
            b.PAIDDT AS tanggal,
            TRIM(COALESCE(b.TRANSNO, '')) AS no_invoice
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        WHERE {$whereSql}
        ORDER BY b.PAIDDT DESC, b.CUSTID DESC, b.BILLCD DESC
        LIMIT " . (int) $fetchLimit . " OFFSET " . (int) $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, (string) $v, PDO::PARAM_STR);
    }
    $stmt->execute();
    $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasMore = count($raw) > $limit;
    if ($hasMore) {
        $raw = array_slice($raw, 0, $limit);
    }

    $rows = [];
    foreach ($raw as $r) {
        $rows[] = [
            'sekolah' => trim((string) ($r['sekolah'] ?? '')),
            'nis' => trim((string) ($r['nis'] ?? '')),
            'nama' => trim((string) ($r['nama'] ?? '')),
            'tanggal' => $r['tanggal'] ?? null,
            'nominal' => 2000,
            'no_invoice' => trim((string) ($r['no_invoice'] ?? '')),
        ];
    }

    return [
        'rows' => $rows,
        'meta' => [
            'has_more' => $hasMore,
            'total_filtered' => $totalFiltered,
            'include_total' => $includeTotal,
            't_select_ms' => round((microtime(true) - $tWall0) * 1000, 2),
        ],
    ];
}

/**
 * Daftar siswa + saldo dari tabel sccttran: SUM(KREDIT) − SUM(DEBET) per CUSTID (tanpa view v_saldo_va).
 */
function getSaldoVirtualAccountRows(array $req): array
{
    $tWall0 = microtime(true);
    $pdo = dbConnectPdo();

    $limit = min(100, max(1, (int) ($req['limit'] ?? 25)));
    $offset = max(0, (int) ($req['offset'] ?? 0));
    $fetchLimit = $limit + 1;

    $thnAngkatan = trim((string) ($req['thn_angkatan'] ?? ''));
    $sekolah = trim((string) ($req['sekolah'] ?? ''));
    $kelasId = trim((string) ($req['kelas_id'] ?? ''));
    $cari = trim((string) ($req['cari'] ?? ''));
    $thnAngkatanBase = trim((string) preg_replace('/\s*-\s*.*/', '', $thnAngkatan));

    $where = ['1=1'];
    $params = [];

    if ($thnAngkatan !== '') {
        $where[] = '(TRIM(c.DESC04) = :sv_ang OR TRIM(c.DESC04) = :sv_ang_base)';
        $params[':sv_ang'] = $thnAngkatan;
        $params[':sv_ang_base'] = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thnAngkatan;
    }
    if ($sekolah !== '') {
        $where[] = '(COALESCE(NULLIF(TRIM(mk.unit), \'\'), TRIM(c.CODE02)) LIKE :sv_sek)';
        $params[':sv_sek'] = '%' . $sekolah . '%';
    }
    if ($kelasId !== '') {
        $where[] = 'TRIM(c.CODE03) = :sv_kelas';
        $params[':sv_kelas'] = $kelasId;
    }
    if ($cari !== '') {
        $where[] = '(TRIM(c.NOCUST) LIKE :sv_c1 OR TRIM(c.NMCUST) LIKE :sv_c2)';
        $like = '%' . $cari . '%';
        $params[':sv_c1'] = $like;
        $params[':sv_c2'] = $like;
    }

    $whereSql = implode(' AND ', $where);

    // Agregasi sekali di sccttran (hindari subquery terkorelasi → kurang risiko error 1615).
    $sql = "
        SELECT
            c.CUSTID AS custid,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NMCUST) AS nama,
            TRIM(c.NUM2ND) AS no_pendaftaran,
            COALESCE(NULLIF(TRIM(mk.unit), ''), TRIM(c.CODE02), '') AS unit,
            COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC02), '') AS kelas,
            TRIM(c.DESC01) AS jenjang,
            TRIM(c.DESC04) AS angkatan,
            COALESCE(tr.saldo_net, 0) AS saldo
        FROM scctcust c
        LEFT JOIN mst_kelas mk ON CAST(mk.id AS CHAR) = TRIM(c.CODE03)
        LEFT JOIN (
            SELECT
                CUSTID,
                CAST(COALESCE(SUM(KREDIT), 0) AS SIGNED) - CAST(COALESCE(SUM(DEBET), 0) AS SIGNED) AS saldo_net
            FROM sccttran
            GROUP BY CUSTID
        ) tr ON tr.CUSTID = c.CUSTID
        WHERE {$whereSql}
        ORDER BY c.CUSTID ASC
        LIMIT " . (int) $fetchLimit . " OFFSET " . (int) $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, (string) $v, PDO::PARAM_STR);
    }
    $stmt->execute();
    $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasMore = count($raw) > $limit;
    if ($hasMore) {
        $raw = array_slice($raw, 0, $limit);
    }

    $rows = [];
    foreach ($raw as $r) {
        $nis = trim((string) ($r['nis'] ?? ''));
        $digits = preg_replace('/\D+/', '', $nis);
        $rows[] = [
            'custid' => (int) ($r['custid'] ?? 0),
            'nis' => $nis,
            'no_va' => '7510050' . ($digits !== '' ? $digits : '0'),
            'nama' => trim((string) ($r['nama'] ?? '')),
            'no_pendaftaran' => trim((string) ($r['no_pendaftaran'] ?? '')),
            'unit' => trim((string) ($r['unit'] ?? '')),
            'kelas' => trim((string) ($r['kelas'] ?? '')),
            'jenjang' => trim((string) ($r['jenjang'] ?? '')),
            'angkatan' => trim((string) ($r['angkatan'] ?? '')),
            'saldo' => (int) ($r['saldo'] ?? 0),
        ];
    }

    return [
        'rows' => $rows,
        'meta' => [
            't_select_ms' => round((microtime(true) - $tWall0) * 1000, 2),
            'has_more' => $hasMore,
        ],
    ];
}

/**
 * Mutasi VA per siswa (sccttran) + total debet/kredit/saldo (sesuai filter cari).
 */
function getSaldoVirtualAccountMutasi(array $req): array
{
    $custid = (int) ($req['custid'] ?? 0);
    if ($custid <= 0) {
        return ['error' => 'custid tidak valid', 'siswa' => null, 'rows' => [], 'totals' => ['debet' => 0, 'kredit' => 0, 'saldo' => 0], 'meta' => []];
    }

    $pdo = dbConnectPdo();
    $cari = trim((string) ($req['cari'] ?? ''));
    $limit = min(100, max(1, (int) ($req['limit'] ?? 25)));
    $offset = max(0, (int) ($req['offset'] ?? 0));
    $fetchLimit = $limit + 1;

    $stS = $pdo->prepare("
        SELECT
            c.CUSTID AS custid,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NMCUST) AS nama,
            COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC02), '') AS kelas,
            TRIM(c.DESC04) AS angkatan,
            TRIM(c.CODE02) AS unit
        FROM scctcust c
        LEFT JOIN mst_kelas mk ON CAST(mk.id AS CHAR) = TRIM(c.CODE03)
        WHERE c.CUSTID = :cid
        LIMIT 1
    ");
    $stS->execute([':cid' => $custid]);
    $srow = $stS->fetch(PDO::FETCH_ASSOC);
    if (!$srow) {
        return ['error' => 'Siswa tidak ditemukan', 'siswa' => null, 'rows' => [], 'totals' => ['debet' => 0, 'kredit' => 0, 'saldo' => 0], 'meta' => []];
    }

    $nis = trim((string) ($srow['nis'] ?? ''));
    $digits = preg_replace('/\D+/', '', $nis);
    $noVa = '7510050' . ($digits !== '' ? $digits : '0');

    $whereTran = ['t.CUSTID = :cid'];
    $paramsTran = [':cid' => $custid];
    if ($cari !== '') {
        $whereTran[] = '(
            TRIM(COALESCE(t.METODE, \'\')) LIKE :c1
            OR TRIM(COALESCE(t.HELPDESK, \'\')) LIKE :c2
            OR TRIM(COALESCE(t.NOREFF, \'\')) LIKE :c3
        )';
        $like = '%' . $cari . '%';
        $paramsTran[':c1'] = $like;
        $paramsTran[':c2'] = $like;
        $paramsTran[':c3'] = $like;
    }
    $whereTranSql = implode(' AND ', $whereTran);

    $stTot = $pdo->prepare("
        SELECT
            CAST(COALESCE(SUM(t.DEBET), 0) AS SIGNED) AS total_debet,
            CAST(COALESCE(SUM(t.KREDIT), 0) AS SIGNED) AS total_kredit
        FROM sccttran t
        WHERE {$whereTranSql}
    ");
    foreach ($paramsTran as $k => $v) {
        $stTot->bindValue($k, $v, str_starts_with((string) $k, ':cid') ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stTot->execute();
    $totRow = $stTot->fetch(PDO::FETCH_ASSOC) ?: [];
    $tDebet = (int) ($totRow['total_debet'] ?? 0);
    $tKredit = (int) ($totRow['total_kredit'] ?? 0);

    $sqlList = "
        SELECT
            TRIM(COALESCE(t.METODE, '')) AS metode,
            t.TRXDATE AS trxdate,
            CAST(COALESCE(t.DEBET, 0) AS SIGNED) AS debet,
            CAST(COALESCE(t.KREDIT, 0) AS SIGNED) AS kredit
        FROM sccttran t
        WHERE {$whereTranSql}
        ORDER BY t.TRXDATE DESC, t.METODE ASC
        LIMIT " . (int) $fetchLimit . " OFFSET " . (int) $offset;

    $stL = $pdo->prepare($sqlList);
    foreach ($paramsTran as $k => $v) {
        $stL->bindValue($k, $v, str_starts_with((string) $k, ':cid') ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stL->execute();
    $raw = $stL->fetchAll(PDO::FETCH_ASSOC);

    $hasMore = count($raw) > $limit;
    if ($hasMore) {
        $raw = array_slice($raw, 0, $limit);
    }

    $rows = [];
    foreach ($raw as $r) {
        $rows[] = [
            'metode' => trim((string) ($r['metode'] ?? '')),
            'trxdate' => $r['trxdate'] ?? null,
            'debet' => (int) ($r['debet'] ?? 0),
            'kredit' => (int) ($r['kredit'] ?? 0),
        ];
    }

    $siswa = [
        'custid' => $custid,
        'nis' => $nis,
        'nama' => trim((string) ($srow['nama'] ?? '')),
        'kelas' => trim((string) ($srow['kelas'] ?? '')),
        'angkatan' => trim((string) ($srow['angkatan'] ?? '')),
        'no_va' => $noVa,
        'unit' => trim((string) ($srow['unit'] ?? '')),
        'saldo' => $tKredit - $tDebet,
    ];

    return [
        'siswa' => $siswa,
        'rows' => $rows,
        'totals' => [
            'debet' => $tDebet,
            'kredit' => $tKredit,
            'saldo' => $tKredit - $tDebet,
        ],
        'meta' => [
            'has_more' => $hasMore,
            'cari' => $cari,
        ],
    ];
}

/**
 * Semua baris transaksi VA (sccttran) dengan join siswa — halaman Data Transaksi.
 * Tanpa COUNT(*); LIMIT+1 untuk indikator halaman berikutnya.
 */
function getDataTransaksiSccttran(array $req): array
{
    $tWall0 = microtime(true);
    $pdo = dbConnectPdo();

    $limit = min(100, max(1, (int) ($req['limit'] ?? 25)));
    $offset = max(0, (int) ($req['offset'] ?? 0));
    $fetchLimit = $limit + 1;

    $tglDari = trim((string) ($req['tgl_dari'] ?? ''));
    $tglSampai = trim((string) ($req['tgl_sampai'] ?? ''));
    $thnAngkatan = trim((string) ($req['thn_angkatan'] ?? ''));
    $sekolah = trim((string) ($req['sekolah'] ?? ''));
    $kelasId = trim((string) ($req['kelas_id'] ?? ''));
    $nis = trim((string) ($req['nis'] ?? ''));
    $nama = trim((string) ($req['nama'] ?? ''));
    $cari = trim((string) ($req['cari'] ?? ''));

    $thnAngkatanBase = trim((string) preg_replace('/\s*-\s*.*/', '', $thnAngkatan));

    $dDari = penerimaanParseYmd($tglDari);
    $dSampai = penerimaanParseYmd($tglSampai);

    $where = ['1=1'];
    $params = [];

    if ($tglDari !== '' && $tglSampai !== '' && $dDari && $dSampai) {
        $where[] = 't.TRXDATE >= :dt_trx_start AND t.TRXDATE < :dt_trx_end_excl';
        $params[':dt_trx_start'] = $dDari->format('Y-m-d H:i:s');
        $params[':dt_trx_end_excl'] = $dSampai->modify('+1 day')->format('Y-m-d H:i:s');
    } elseif ($tglDari !== '' && $dDari) {
        $where[] = 't.TRXDATE >= :dt_trx_start';
        $params[':dt_trx_start'] = $dDari->format('Y-m-d H:i:s');
    } elseif ($tglSampai !== '' && $dSampai) {
        $where[] = 't.TRXDATE < :dt_trx_end_excl';
        $params[':dt_trx_end_excl'] = $dSampai->modify('+1 day')->format('Y-m-d H:i:s');
    }

    if ($thnAngkatan !== '') {
        $where[] = '(TRIM(c.DESC04) = :dt_ang OR TRIM(c.DESC04) = :dt_ang_base)';
        $params[':dt_ang'] = $thnAngkatan;
        $params[':dt_ang_base'] = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thnAngkatan;
    }
    if ($sekolah !== '') {
        $where[] = '(COALESCE(NULLIF(TRIM(mk.unit), \'\'), TRIM(c.CODE02)) LIKE :dt_sek)';
        $params[':dt_sek'] = '%' . $sekolah . '%';
    }
    if ($kelasId !== '') {
        $where[] = 'TRIM(c.CODE03) = :dt_kelas';
        $params[':dt_kelas'] = $kelasId;
    }
    if ($nis !== '') {
        $where[] = 'TRIM(c.NOCUST) LIKE :dt_nis';
        $params[':dt_nis'] = '%' . $nis . '%';
    }
    if ($nama !== '') {
        $where[] = 'TRIM(c.NMCUST) LIKE :dt_nama';
        $params[':dt_nama'] = '%' . $nama . '%';
    }
    if ($cari !== '') {
        $like = '%' . $cari . '%';
        $where[] = '(
            TRIM(COALESCE(t.METODE, \'\')) LIKE :dt_c1
            OR TRIM(COALESCE(t.HELPDESK, \'\')) LIKE :dt_c2
            OR TRIM(COALESCE(t.NOREFF, \'\')) LIKE :dt_c3
            OR TRIM(c.NOCUST) LIKE :dt_c4
            OR TRIM(c.NMCUST) LIKE :dt_c5
        )';
        $params[':dt_c1'] = $like;
        $params[':dt_c2'] = $like;
        $params[':dt_c3'] = $like;
        $params[':dt_c4'] = $like;
        $params[':dt_c5'] = $like;
    }

    $whereSql = implode(' AND ', $where);

    $joinKelasSql = ($sekolah !== '')
        ? 'LEFT JOIN mst_kelas mk ON CAST(mk.id AS CHAR) = TRIM(c.CODE03)'
        : '';

    $sql = "
        SELECT
            t.CUSTID AS custid,
            TRIM(c.NOCUST) AS nis,
            TRIM(c.NMCUST) AS nama,
            TRIM(COALESCE(t.METODE, '')) AS metode,
            t.TRXDATE AS trxdate,
            CAST(COALESCE(t.DEBET, 0) AS SIGNED) AS debet,
            CAST(COALESCE(t.KREDIT, 0) AS SIGNED) AS kredit
        FROM sccttran t
        INNER JOIN scctcust c ON c.CUSTID = t.CUSTID
        {$joinKelasSql}
        WHERE {$whereSql}
        ORDER BY t.TRXDATE DESC, t.CUSTID DESC
        LIMIT " . (int) $fetchLimit . ' OFFSET ' . (int) $offset;

    $selectError = null;
    $raw = [];
    try {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, (string) $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $selectError = $e->getMessage();
    }

    $hasMore = count($raw) > $limit;
    if ($hasMore) {
        $raw = array_slice($raw, 0, $limit);
    }

    $rows = [];
    foreach ($raw as $r) {
        $nisR = trim((string) ($r['nis'] ?? ''));
        $digits = preg_replace('/\D+/', '', $nisR);
        $rows[] = [
            'custid' => (int) ($r['custid'] ?? 0),
            'nis' => $nisR,
            'no_va' => '7510050' . ($digits !== '' ? $digits : '0'),
            'nama' => trim((string) ($r['nama'] ?? '')),
            'metode' => trim((string) ($r['metode'] ?? '')),
            'trxdate' => $r['trxdate'] ?? null,
            'debet' => (int) ($r['debet'] ?? 0),
            'kredit' => (int) ($r['kredit'] ?? 0),
        ];
    }

    return [
        'rows' => $rows,
        'meta' => [
            'has_more' => $hasMore,
            't_select_ms' => round((microtime(true) - $tWall0) * 1000, 2),
            'select_error' => $selectError,
        ],
    ];
}

function getManualPembayaranBankOptions(): array
{
    return [
        ['fidbank' => '1140000', 'label' => 'Manual CASH'],
        ['fidbank' => '1140001', 'label' => 'Manual BMI'],
        ['fidbank' => '1140002', 'label' => 'Manual SALDO'],
        ['fidbank' => '1140003', 'label' => 'Transfer Bank Lain'],
        ['fidbank' => '1200001', 'label' => 'Loket Manual - Beasiswa'],
        ['fidbank' => '1200002', 'label' => 'Loket Manual - Potongan'],
    ];
}

function createManualPembayaran(array $req): array
{
    $custid = (int) ($req['custid'] ?? 0);
    $fidbank = trim((string) ($req['fidbank'] ?? ''));
    $selectedBillcds = $req['selected_billcds'] ?? [];
    $paiddtReq = trim((string) ($req['paiddt'] ?? ''));

    if ($custid <= 0) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'custid wajib diisi'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $allowedFidbank = array_column(getManualPembayaranBankOptions(), 'fidbank');
    if (!in_array($fidbank, $allowedFidbank, true)) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'fidbank tidak valid'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_array($selectedBillcds) || $selectedBillcds === []) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'selected_billcds wajib diisi'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $billcds = [];
    foreach ($selectedBillcds as $v) {
        $b = trim((string) $v);
        if ($b !== '') {
            $billcds[$b] = true;
        }
    }
    $billcds = array_keys($billcds);
    if ($billcds === []) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'selected_billcds tidak valid'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Waktu pembayaran = saat ini (timezone Asia/Jakarta sudah di-set di awal file).
    $paiddt = date('Y-m-d H:i:s');

    $pdo = dbConnectPdo();
    $inParams = [];
    $params = [
        ':custid' => $custid,
        ':fidbank' => $fidbank,
        ':paiddt' => $paiddt,
    ];
    foreach ($billcds as $i => $billcd) {
        $ph = ':billcd_' . $i;
        $inParams[] = $ph;
        $params[$ph] = $billcd;
    }

    // Hitung total tagihan terpilih yang benar-benar belum lunas & boleh bayar.
    $sumSql = "
        SELECT CAST(COALESCE(SUM(COALESCE(BILLAM, 0)), 0) AS SIGNED) AS TOTAL_BAYAR
        FROM scctbill
        WHERE CUSTID = :custid
          AND BILLCD IN (" . implode(', ', $inParams) . ")
          AND FSTSBolehBayar = 1
          AND (PAIDST = '0' OR PAIDST = 0 OR TRIM(CAST(PAIDST AS CHAR)) = '0')
    ";
    $stmtSum = $pdo->prepare($sumSql);
    $sumParams = [':custid' => $custid];
    foreach ($billcds as $i => $billcd) {
        $sumParams[':billcd_' . $i] = $billcd;
    }
    foreach ($sumParams as $k => $v) {
        $stmtSum->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmtSum->execute();
    $sumRow = $stmtSum->fetch();
    $totalBayar = (int) (($sumRow["TOTAL_BAYAR"] ?? 0));
    if ($totalBayar <= 0) {
        http_response_code(422);
        echo json_encode([
            'status' => 422,
            'message' => 'Total bayar tidak valid atau tagihan sudah lunas.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Khusus Manual SALDO: validasi saldo cukup sebelum update bill.
    if ($fidbank === '1140002') {
        $saldoVa = 0;
        try {
            $stmtSaldo = $pdo->prepare("
                SELECT CAST(COALESCE(SALDO, 0) AS SIGNED) AS SALDO
                FROM v_saldo_va
                WHERE CUSTID = :CUSTID
                LIMIT 1
            ");
            $stmtSaldo->execute([":CUSTID" => $custid]);
            $saldoRow = $stmtSaldo->fetch();
            if (is_array($saldoRow)) {
                $saldoVa = (int) ($saldoRow["SALDO"] ?? 0);
            }
        } catch (Throwable $e) {
            // ignore
        }
        if ($saldoVa === 0) {
            try {
                $stCust = $pdo->prepare("SELECT TRIM(NOCUST) AS NOCUST FROM scctcust WHERE CUSTID = :custid LIMIT 1");
                $stCust->execute([":custid" => $custid]);
                $rw = $stCust->fetch();
                $nocust = trim((string) ($rw["NOCUST"] ?? ""));
                $nocustDigits = preg_replace('/\D+/', '', $nocust);
                $noVa = "7510050" . ($nocustDigits !== "" ? $nocustDigits : "0");
                $stmtSaldo2 = $pdo->prepare("
                    SELECT CAST(COALESCE(SALDO, 0) AS SIGNED) AS SALDO
                    FROM v_saldo_va
                    WHERE TRIM(CAST(NOCUST AS CHAR)) = TRIM(:nocust)
                       OR TRIM(CAST(NO_VA AS CHAR)) = TRIM(:nova)
                       OR TRIM(CAST(VA AS CHAR)) = TRIM(:nova)
                    LIMIT 1
                ");
                $stmtSaldo2->execute([":nocust" => $nocust, ":nova" => $noVa]);
                $rw2 = $stmtSaldo2->fetch();
                if (is_array($rw2)) {
                    $saldoVa = (int) ($rw2["SALDO"] ?? 0);
                }
            } catch (Throwable $e2) {
                // ignore
            }
        }
        if ($saldoVa === 0) {
            try {
                $stmtTranSaldo = $pdo->prepare("
                    SELECT
                        CAST(COALESCE(SUM(KREDIT), 0) AS SIGNED) - CAST(COALESCE(SUM(DEBET), 0) AS SIGNED) AS SALDO_NETTO
                    FROM sccttran
                    WHERE CUSTID = :custid
                ");
                $stmtTranSaldo->execute([":custid" => $custid]);
                $rw3 = $stmtTranSaldo->fetch();
                if (is_array($rw3)) {
                    $saldoVa = (int) ($rw3["SALDO_NETTO"] ?? 0);
                }
            } catch (Throwable $e3) {
                // ignore
            }
        }

        if ($saldoVa < $totalBayar) {
            http_response_code(422);
            echo json_encode([
                'status' => 422,
                'message' => 'Saldo tidak cukup untuk pembayaran ini.',
                'data' => [
                    'saldo' => $saldoVa,
                    'total_bayar' => $totalBayar,
                ],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $sql = "
        UPDATE scctbill
        SET
            PAIDST = 1,
            PAIDDT = :paiddt,
            FIDBANK = :fidbank
        WHERE CUSTID = :custid
          AND BILLCD IN (" . implode(', ', $inParams) . ")
          AND FSTSBolehBayar = 1
          AND (PAIDST = '0' OR PAIDST = 0 OR TRIM(CAST(PAIDST AS CHAR)) = '0')
    ";
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();

        $updated = $stmt->rowCount();
        if ($updated <= 0) {
            $pdo->rollBack();
            http_response_code(422);
            echo json_encode([
                'status' => 422,
                'message' => 'Tidak ada tagihan yang diperbarui. Pastikan tagihan belum lunas dan pilihan centang sudah benar.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $insertedTran = 0;
        if ($fidbank === '1140002') {
            $noref = implode(',', $billcds);
            $stmtTranIns = $pdo->prepare("
                INSERT INTO sccttran
                    (CUSTID, METODE, TRXDATE, NOREFF, FIDBANK, KDCHANNEL, DEBET, KREDIT, REFFBANK, TRANSNO, HELPDESK)
                VALUES
                    (:custid, :metode, :trxdate, :noreff, :fidbank, :kdchannel, :debet, :kredit, :reffbank, :transno, :helpdesk)
            ");
            $stmtTranIns->execute([
                ':custid' => $custid,
                ':metode' => 'FROM SALDO',
                ':trxdate' => $paiddt,
                ':noreff' => $noref,
                ':fidbank' => $fidbank,
                ':kdchannel' => 11,
                ':debet' => $totalBayar,
                ':kredit' => 0,
                ':reffbank' => '',
                ':transno' => '',
                ':helpdesk' => '',
            ]);
            $insertedTran = $stmtTranIns->rowCount();
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'custid' => $custid,
        'fidbank' => $fidbank,
        'paiddt' => $paiddt,
        'billcds' => $billcds,
        'total_bayar' => $totalBayar,
        'updated' => $updated,
        'inserted_sccttran' => $insertedTran ?? 0,
    ];
}

function updateDataTagihanUrutan(array $req): array
{
    $custid    = (int) ($req['custid'] ?? 0);
    $billcd    = trim((string) ($req['billcd'] ?? ''));
    $direction = strtolower(trim((string) ($req['direction'] ?? '')));

    if ($custid <= 0 || $billcd === '' || !in_array($direction, ['up', 'down'], true)) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'custid, billcd, dan direction (up|down) wajib valid'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();
    $st  = $pdo->prepare('
        SELECT COALESCE(furutan, 0) AS f
        FROM scctbill
        WHERE CUSTID = :c AND BILLCD = :b
        LIMIT 1
    ');
    $st->execute([':c' => $custid, ':b' => $billcd]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Tagihan tidak ditemukan'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cur   = (int) ($row['f'] ?? 0);
    $newF  = $direction === 'up' ? max(1, $cur - 1) : $cur + 1;
    $up    = $pdo->prepare('UPDATE scctbill SET furutan = :f WHERE CUSTID = :c AND BILLCD = :b');
    $up->execute([':f' => $newF, ':c' => $custid, ':b' => $billcd]);

    return [
        'custid'   => $custid,
        'billcd'   => $billcd,
        'furutan'  => $newF,
    ];
}

/**
 * Hapus satu tagihan belum lunas: scctbill_detail lalu scctbill.
 *
 * @return array{ok: bool, message: string}
 */
function hapusTagihanDeleteUnpaidBill(PDO $pdo, int $custid, string $billcd): array
{
    $billcd = trim($billcd);
    if ($custid <= 0 || $billcd === '') {
        return ['ok' => false, 'message' => 'custid/billcd tidak valid'];
    }

    $detailCol = detectScctbillDetailCustColumn($pdo);
    $chk = $pdo->prepare("
        SELECT 1 FROM scctbill
        WHERE CUSTID = :c AND BILLCD = :b
          AND FSTSBolehBayar = 1
          AND (PAIDST = '0' OR PAIDST = 0 OR TRIM(CAST(PAIDST AS CHAR)) = '0')
        LIMIT 1
    ");
    $chk->execute([':c' => $custid, ':b' => $billcd]);
    if (!$chk->fetchColumn()) {
        return ['ok' => false, 'message' => 'Tagihan tidak ditemukan atau sudah lunas.'];
    }

    $delD = $pdo->prepare("DELETE FROM scctbill_detail WHERE BILLCD = :b AND {$detailCol} = :c");
    $delD->execute([':b' => $billcd, ':c' => $custid]);

    $delB = $pdo->prepare("
        DELETE FROM scctbill
        WHERE CUSTID = :c AND BILLCD = :b
          AND FSTSBolehBayar = 1
          AND (PAIDST = '0' OR PAIDST = 0 OR TRIM(CAST(PAIDST AS CHAR)) = '0')
    ");
    $delB->execute([':c' => $custid, ':b' => $billcd]);
    if ($delB->rowCount() === 0) {
        return ['ok' => false, 'message' => 'Gagal menghapus header tagihan.'];
    }

    return ['ok' => true, 'message' => ''];
}

function deleteDataTagihanRow(array $req): array
{
    $custid = (int) ($req['custid'] ?? 0);
    $billcd = trim((string) ($req['billcd'] ?? ''));

    if ($custid <= 0 || $billcd === '') {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'custid dan billcd wajib diisi'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();
    $r = hapusTagihanDeleteUnpaidBill($pdo, $custid, $billcd);
    if (!$r['ok']) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => $r['message']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return ['deleted' => 1];
}

function createBuatTagihan(array $req): array
{
    $thn_akademik = trim((string) ($req['thn_akademik'] ?? ''));
    $thn_angkatan = trim((string) ($req['thn_angkatan'] ?? ''));
    $kelas_id     = trim((string) ($req['kelas_id']     ?? ''));
    $fungsi       = trim((string) ($req['fungsi']       ?? ''));
    $tagihan      = trim((string) ($req['tagihan']      ?? ''));
    $custids      = $req['custids']      ?? [];
    $kode_akuns   = $req['kode_akuns']   ?? [];

    if ($thn_akademik === '' || $kelas_id === '') {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'thn_akademik dan kelas_id wajib diisi'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $fungsi = resolveBillacPeriodeByTagihan($tagihan, $fungsi);
    if ($fungsi === '') {
        $fungsi = date('Ym');
    }

    if (!is_array($custids) || count($custids) === 0) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'custids wajib diisi'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_array($kode_akuns) || count($kode_akuns) === 0) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'kode_akuns wajib diisi'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $custids    = array_values(array_filter(array_map('intval', $custids), fn($v) => $v > 0));
    $kode_akuns = array_values(array_filter(array_map('trim', $kode_akuns), fn($v) => $v !== ''));

    if (count($custids) === 0) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'custids tidak valid'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (count($kode_akuns) === 0) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => 'kode_akuns tidak valid'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();
    $thnAngkatanBase = trim((string) preg_replace('/\s*-\s*.*/', '', $thn_angkatan));

    $resolvedKodeProd = $kelas_id;
    if ($kelas_id !== '') {
        $stmtResolveDirect = $pdo->prepare("
            SELECT 1
            FROM u_daftar_harga
            WHERE TRIM(kode_prod) = :kode_prod
            LIMIT 1
        ");
        $stmtResolveDirect->execute([':kode_prod' => $kelas_id]);
        $hasDirect = (bool) $stmtResolveDirect->fetchColumn();

        if (!$hasDirect) {
            $kelasPad2 = str_pad((string) ((int) $kelas_id), 2, '0', STR_PAD_LEFT);
            $sqlResolve = "
                SELECT TRIM(kode_prod) AS kode_prod
                FROM u_daftar_harga
                WHERE TRIM(kode_fak) = :kelas_pad2
            ";
            $paramsResolve = [':kelas_pad2' => $kelasPad2];
            if ($thn_angkatan !== '' || $thnAngkatanBase !== '') {
                $sqlResolve .= " AND (TRIM(thn_masuk) = :thn_angkatan_full OR TRIM(thn_masuk) = :thn_angkatan_base)";
                $paramsResolve[':thn_angkatan_full'] = $thn_angkatan;
                $paramsResolve[':thn_angkatan_base'] = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thn_angkatan;
            }
            $sqlResolve .= " ORDER BY urut ASC LIMIT 1";
            $stmtResolve = $pdo->prepare($sqlResolve);
            $stmtResolve->execute($paramsResolve);
            $rowResolve = $stmtResolve->fetch();
            $resolvedKodeProd = trim((string) ($rowResolve['kode_prod'] ?? $kelas_id));
        }
    }

    $stmtDaftarAll = $pdo->prepare("
        SELECT
            TRIM(KodeAkun) AS KodeAkun,
            COALESCE(fid, 1) AS FID,
            COALESCE(
                NULLIF(TRIM(NamaAkun), ''),
                (
                    SELECT TRIM(a.NamaAkun)
                    FROM u_akun a
                    WHERE TRIM(a.KodeAkun) = TRIM(u_daftar_harga.KodeAkun)
                    LIMIT 1
                )
            ) AS NamaAkun,
            TRIM(nominal)  AS nominal,
            TRIM(NoRek)    AS NoRek
        FROM u_daftar_harga
        WHERE TRIM(kode_prod) = ?
          AND (
              REPLACE(TRIM(thn_masuk), ' ', '') = REPLACE(TRIM(?), ' ', '')
              OR REPLACE(TRIM(thn_masuk), ' ', '') = REPLACE(TRIM(?), ' ', '')
              OR REPLACE(TRIM(thn_masuk), ' ', '') LIKE CONCAT(REPLACE(TRIM(?), ' ', ''), '%')
          )
        ORDER BY urut ASC
    ");
    $stmtDaftarAll->execute([
        $resolvedKodeProd,
        $thn_angkatan,
        $thnAngkatanBase !== '' ? $thnAngkatanBase : $thn_angkatan,
        $thnAngkatanBase !== '' ? $thnAngkatanBase : $thn_angkatan,
    ]);
    $allDaftarHarga = $stmtDaftarAll->fetchAll();

    $selectedExact = array_map(static fn($v) => trim((string) $v), $kode_akuns);
    $selectedNorm = array_map(static fn($v) => preg_replace('/\D+/', '', trim((string) $v)), $selectedExact);
    $selectedMap = array_fill_keys($selectedExact, true);
    $selectedNormMap = array_fill_keys($selectedNorm, true);

    $daftarHarga = array_values(array_filter($allDaftarHarga, static function ($row) use ($selectedMap, $selectedNormMap) {
        $kode = trim((string) ($row['KodeAkun'] ?? ''));
        $kodeNorm = preg_replace('/\D+/', '', $kode);
        return isset($selectedMap[$kode]) || ($kodeNorm !== '' && isset($selectedNormMap[$kodeNorm]));
    }));

    writeLog([
        'scope' => 'createBuatTagihan:resolve',
        'kelas_id' => $kelas_id,
        'resolved_kode_prod' => $resolvedKodeProd,
        'thn_angkatan_full' => $thn_angkatan,
        'thn_angkatan_base' => $thnAngkatanBase,
        'kode_akuns' => $kode_akuns,
        'total_daftar_harga_all' => count($allDaftarHarga),
        'total_daftar_harga' => count($daftarHarga),
    ]);

    if (count($daftarHarga) === 0) {
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Daftar harga tidak ditemukan untuk kelas dan tahun angkatan tersebut'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $inserted = 0;
    $skipped  = 0;
    $errors   = [];

    $stmtInsert = $pdo->prepare("
        INSERT INTO scctbill
            (CUSTID, BILLCD, BILLAC, BILLNM, BILLAM, PAIDST, FSTSBolehBayar, BTA, FTGLTagihan, furutan)
        VALUES
            (:CUSTID, :BILLCD, :BILLAC, :BILLNM, :BILLAM, '0', 1, :BTA, NOW(), :FURUTAN)
    ");
    $stmtBillAa = $pdo->prepare("
        SELECT AA
        FROM scctbill
        WHERE CUSTID = :c AND BILLCD = :b
        ORDER BY AA DESC
        LIMIT 1
    ");
    $detailCustCol = detectScctbillDetailCustColumn($pdo);
    $stmtInsertDetail = $pdo->prepare("
        INSERT INTO scctbill_detail
            (AA, KodePost, BILLAM, {$detailCustCol}, FID, tahun, periode, BILLCD)
        VALUES
            (:AA, :KodePost, :BILLAM, :CUST_VAL, :FID, :tahun, :periode, :BILLCD)
    ");

    $stmtMaxUrutGlobal = $pdo->prepare("SELECT COALESCE(MAX(furutan), 0) AS mx FROM scctbill");
    $nextUrutGlobal = 1;

    $pdo->beginTransaction();

    try {
        $stmtMaxUrutGlobal->execute();
        $mxGlobal = (int) ($stmtMaxUrutGlobal->fetchColumn() ?: 0);
        $nextUrutGlobal = max(1, $mxGlobal + 1);

        foreach ($custids as $custid) {
            foreach ($daftarHarga as $dh) {
                $kodeAkun = $dh['KodeAkun'];
                $namaAkun = $dh['NamaAkun'];
                $nominal  = (int) $dh['nominal'];
                $billac   = $fungsi;
                $furutan = $nextUrutGlobal;
                $billcd = buildTagihanBillCd($thn_akademik, $furutan, 'M');

                try {
                    $stmtInsert->execute([
                        ':CUSTID' => $custid,
                        ':BILLCD' => $billcd,
                        ':BILLAC' => $billac,
                        ':BILLNM' => $tagihan !== '' ? $tagihan : $namaAkun,
                        ':BILLAM' => $nominal,
                        ':BTA'    => $thn_akademik,
                        ':FURUTAN' => $furutan,
                    ]);
                    $stmtBillAa->execute([':c' => $custid, ':b' => $billcd]);
                    $billAa = (int) ($stmtBillAa->fetchColumn() ?: 0);
                    $stmtInsertDetail->execute([
                        ':AA' => $billAa,
                        ':KodePost' => trim((string) $kodeAkun),
                        ':BILLAM' => $nominal,
                        ':CUST_VAL' => $custid,
                        ':FID' => null,
                        ':tahun' => date('Y'),
                        ':periode' => date('m'),
                        ':BILLCD' => $billcd,
                    ]);
                    $nextUrutGlobal++;
                    $inserted++;
                } catch (Throwable $e) {
                    $errors[] = ['custid' => $custid, 'kode_akun' => $kodeAkun, 'error' => $e->getMessage()];
                }
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 500, 'message' => 'Gagal menyimpan tagihan: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return [
        'inserted' => $inserted,
        'skipped'  => $skipped,
        'errors'   => $errors,
    ];
}

function resolveBillacPeriodeByTagihan(string $tagihan, string $fallback = ''): string
{
    $year = date('Y');
    $currentMonth = date('m');
    $name = mb_strtoupper(trim($tagihan));

    if ($name === '') {
        return $fallback !== '' ? $fallback : ($year . $currentMonth);
    }

    $monthMap = [
        'JANUARI' => '01',
        'JANUARY' => '01',
        'FEBRUARI' => '02',
        'FEBRUARY' => '02',
        'MARET' => '03',
        'MARCH' => '03',
        'APRIL' => '04',
        'MEI' => '05',
        'MAY' => '05',
        'JUNI' => '06',
        'JUNE' => '06',
        'JULI' => '07',
        'JULY' => '07',
        'AGUSTUS' => '08',
        'AUGUST' => '08',
        'SEPTEMBER' => '09',
        'OKTOBER' => '10',
        'OCTOBER' => '10',
        'NOVEMBER' => '11',
        'DESEMBER' => '12',
        'DECEMBER' => '12',
    ];
    foreach ($monthMap as $key => $mm) {
        if (str_contains($name, $key)) {
            return $year . $mm;
        }
    }

    return $year . $currentMonth;
}

function buildTagihanBillCdByMode(string $thnAkademik, int $urutan, string $mode): string
{
    $tahun = date('Y');
    $bulan = date('m');
    $urut = max(1, (int) $urutan);
    $prefix = normalizeBillCdMode($mode);

    return $tahun . '/' . $prefix . $bulan . '-' . $urut;
}

function buildTagihanBillCd(string $thnAkademik, int $urutan, string $mode = 'B'): string
{
    return buildTagihanBillCdByMode($thnAkademik, $urutan, $mode);
}

function normalizeBillCdMode(string $mode): string
{
    $m = strtoupper(trim($mode));
    if (in_array($m, ['M', 'P', 'E', 'B'], true)) {
        return $m;
    }

    return 'B';
}

function detectScctbillDetailCustColumn(PDO $pdo): string
{
    try {
        $st = $pdo->query("SHOW COLUMNS FROM scctbill_detail");
        $rows = is_object($st) ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
        $fields = [];
        foreach ((array) $rows as $r) {
            $f = strtolower(trim((string) ($r['Field'] ?? '')));
            if ($f !== '') {
                $fields[$f] = true;
            }
        }
        if (isset($fields['cust'])) {
            return 'CUST';
        }
        if (isset($fields['custid'])) {
            return 'CUSTID';
        }
    } catch (Throwable $e) {
        // fallback below
    }

    return 'CUSTID';
}

/**
 * Login user dari tabel users (username/email + bcrypt password).
 *
 * @return array{user?: array<string, mixed>, error?: string}
 */
function loginUser(array $req): array
{
    $login = trim((string) ($req['login'] ?? ''));
    $password = (string) ($req['password'] ?? '');
    if ($login === '' || $password === '') {
        return ['error' => 'Username/email dan password wajib diisi.'];
    }

    $pdo = dbConnectPdo();
    $sql = "
        SELECT
            id,
            username,
            name,
            email,
            password,
            is_active,
            unit
        FROM users
        WHERE deleted_at IS NULL
          AND (
            LOWER(TRIM(username)) = LOWER(TRIM(:login_u))
            OR LOWER(TRIM(COALESCE(email, ''))) = LOWER(TRIM(:login_e))
          )
        LIMIT 1
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':login_u', $login, PDO::PARAM_STR);
    $st->bindValue(':login_e', $login, PDO::PARAM_STR);
    $st->execute();
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row || !is_array($row)) {
        return ['error' => 'Username/email atau password salah.'];
    }

    if ((int) ($row['is_active'] ?? 0) !== 1) {
        return ['error' => 'Akun tidak aktif.'];
    }

    $hash = (string) ($row['password'] ?? '');
    if ($hash === '' || !password_verify($password, $hash)) {
        return ['error' => 'Username/email atau password salah.'];
    }

    $uid = (int) ($row['id'] ?? 0);
    if ($uid > 0) {
        try {
            $up = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $up->bindValue(':id', $uid, PDO::PARAM_INT);
            $up->execute();
        } catch (Throwable $e) {
            // abaikan jika gagal update last_login
        }
    }

    return [
        'user' => [
            'id' => $uid,
            'username' => trim((string) ($row['username'] ?? '')),
            'name' => trim((string) ($row['name'] ?? '')),
            'email' => trim((string) ($row['email'] ?? '')),
            'unit' => trim((string) ($row['unit'] ?? '')),
        ],
    ];
}

try {
    loadEnv(__DIR__ . "/.env");

    $req = getJsonInput();

    $token = null;

    if (isset($req["token"]) && is_string($req["token"]) && $req["token"] !== "") {
        $token = $req["token"];
    } elseif (!empty($_POST["token"])) {
        $token = $_POST["token"];
    } elseif (!empty($_SERVER["HTTP_AUTHORIZATION"])) {
        $authHeader = $_SERVER["HTTP_AUTHORIZATION"];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode([
            "status" => 401,
            "message" => "Token wajib diisi"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $jwt = new JWT();
    $key = (string) ($_ENV["JWT_KEY"] ?? "");

    if ($key === "") {
        http_response_code(500);
        echo json_encode([
            "status" => 500,
            "message" => "JWT_KEY belum di set"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $decoded = $jwt->decode($token, $key, ["HS256"]);
        if (is_object($decoded)) {
            $decoded = (array) $decoded;
        }
        $req = array_merge($req, (array) $decoded);
    } catch (Throwable $e) {
        http_response_code(401);
        echo json_encode([
            "status" => 401,
            "message" => "Token JWT tidak valid"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $method = trim((string) ($req["method"] ?? "dashboard"));

    if ($method === 'loginUser') {
        $data = loginUser($req);
        $st = !empty($data['error']) ? 422 : 200;
        http_response_code($st);
        echo json_encode([
            'status' => $st,
            'method' => 'loginUser',
            'message' => (string) ($data['error'] ?? ''),
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "dashboard") {
        $rows = getDashboard();

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "dashboard",
            "data" => $rows
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getFilterBuatTagihan') {
        $data = getFilterBuatTagihan();
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getFilterBuatTagihan',
            'data'   => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getRekapPenerimaanFilterShell') {
        $data = getRekapPenerimaanFilterShell();
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getRekapPenerimaanFilterShell',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getBuatTagihan') {
        $data = getBuatTagihan($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getBuatTagihan',
            'data'   => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getFungsiBuatTagihan') {
        $data = getFungsiBuatTagihan($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getFungsiBuatTagihan',
            'data'   => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'enrichTagihanExcelRows') {
        $data = enrichTagihanExcelRows($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'enrichTagihanExcelRows',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'createTagihanExcelUpload') {
        $result = createTagihanExcelUpload($req);
        http_response_code(201);
        echo json_encode([
            'status'  => 201,
            'method'  => 'createTagihanExcelUpload',
            'message' => 'Upload tagihan excel berhasil diproses',
            'data'    => $result,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getDataTagihan') {
        $data = getDataTagihan($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getDataTagihan',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getDataPenerimaan') {
        $data = getDataPenerimaan($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getDataPenerimaan',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getKartuSiswaPenerimaan') {
        $data = getKartuSiswaPenerimaan($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getKartuSiswaPenerimaan',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getDataPembayaranPerNis') {
        $data = getDataPembayaranPerNis($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getDataPembayaranPerNis',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getHapusTagihanRows') {
        $data = getHapusTagihanRows($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getHapusTagihanRows',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getCekPelunasanRows') {
        $data = getCekPelunasanRows($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getCekPelunasanRows',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getCekPelunasanCards') {
        $data = getCekPelunasanCards($req);
        $st = !empty($data['error']) ? 422 : 200;
        http_response_code($st);
        echo json_encode([
            'status' => $st,
            'method' => 'getCekPelunasanCards',
            'message' => (string) ($data['error'] ?? ''),
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'hapusTagihanSiswaBatch') {
        $data = hapusTagihanSiswaBatch($req);
        $st = isset($data['error']) ? 422 : 200;
        http_response_code($st);
        echo json_encode([
            'status' => $st,
            'method' => 'hapusTagihanSiswaBatch',
            'message' => (string) ($data['error'] ?? ''),
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getSaldoVirtualAccountRows') {
        $data = getSaldoVirtualAccountRows($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getSaldoVirtualAccountRows',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getSaldoVirtualAccountMutasi') {
        $data = getSaldoVirtualAccountMutasi($req);
        $st = !empty($data['error']) ? 404 : 200;
        http_response_code($st);
        echo json_encode([
            'status' => $st,
            'method' => 'getSaldoVirtualAccountMutasi',
            'message' => (string) ($data['error'] ?? ''),
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getDataBiayaAdminRows') {
        $data = getDataBiayaAdminRows($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getDataBiayaAdminRows',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getDataTransaksiSccttran') {
        $data = getDataTransaksiSccttran($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getDataTransaksiSccttran',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'getManualPembayaranBankOptions') {
        $data = getManualPembayaranBankOptions();
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'getManualPembayaranBankOptions',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    if ($method === 'createManualPembayaran') {
        $data = createManualPembayaran($req);
        http_response_code(201);
        echo json_encode([
            'status' => 201,
            'method' => 'createManualPembayaran',
            'message' => 'Pembayaran manual berhasil diproses',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'updateDataTagihanUrutan') {
        $data = updateDataTagihanUrutan($req);
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'method' => 'updateDataTagihanUrutan',
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'deleteDataTagihan') {
        $data = deleteDataTagihanRow($req);
        http_response_code(200);
        echo json_encode([
            'status'  => 200,
            'method'  => 'deleteDataTagihan',
            'message' => 'Tagihan berhasil dihapus',
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "tagihandashboard") {
        $rows = getTagihanDashboard();

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "tagihandashboard",
            "data" => $rows
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "tagihanbayarDashboard") {
        $rows = getTagihanBayarDashboard();

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "tagihanbayarDashboard",
            "data" => $rows
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    if ($method === "getSiswaByKelas") {
        $rows = getSiswaByKelas($req);
        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getSiswaByKelas",
            "data"   => $rows
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "pindahKelas") {
        $result = pindahKelas($req);
        http_response_code(200);
        echo json_encode([
            "status"  => 200,
            "method"  => "pindahKelas",
            "message" => "Pemindahan kelas berhasil",
            "data"    => $result
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    if ($method === "getKelas") {
        $rows = getKelas($req);

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getKelas",
            "data"   => $rows
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'createBuatTagihan') {
        $result = createBuatTagihan($req);
        http_response_code(201);
        echo json_encode([
            'status'  => 201,
            'method'  => 'createBuatTagihan',
            'message' => 'Tagihan berhasil disimpan',
            'data'    => $result
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getKelasByid") {
        $row = getKelasByid($req);

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getKelasByid",
            "data"   => $row
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "createKelas") {
        $row = createKelas($req);

        http_response_code(201);
        echo json_encode([
            "status"  => 201,
            "method"  => "createKelas",
            "message" => "Kelas berhasil ditambahkan",
            "data"    => $row
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "deleteKelas") {
        $row = deleteKelas($req);

        http_response_code(200);
        echo json_encode([
            "status"  => 200,
            "method"  => "deleteKelas",
            "message" => "Kelas berhasil dihapus",
            "data"    => $row
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getAkun") {
        $rows = getAkun($req);

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getAkun",
            "data"   => $rows
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getAkunByKode") {
        $row = getAkunByKode($req);

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getAkunByKode",
            "data"   => $row
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "createAkun") {
        $row = createAkun($req);

        http_response_code(201);
        echo json_encode([
            "status"  => 201,
            "method"  => "createAkun",
            "message" => "Akun berhasil ditambahkan",
            "data"    => $row
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getFilterSiswa") {
        $data = getFilterSiswa();

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getFilterSiswa",
            "data"   => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getSiswa") {
        $rows = getSiswa($req);

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getSiswa",
            "data"   => $rows
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getSiswaByCustid") {
        $row = getSiswaByCustid($req);

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getSiswaByCustid",
            "data"   => $row
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getThnAka") {
        $rows = getThnAka();

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getThnAka",
            "data"   => $rows
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getThnAkaByUrut") {
        $row = getThnAkaByUrut($req);

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getThnAkaByUrut",
            "data"   => $row
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "createThnAka") {
        $row = createThnAka($req);

        http_response_code(201);
        echo json_encode([
            "status"  => 201,
            "method"  => "createThnAka",
            "message" => "Tahun akademik berhasil ditambahkan",
            "data"    => $row
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getBebanPost") {
        $rows = getBebanPost($req);

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getBebanPost",
            "data"   => $rows
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getBebanPostByUrut") {
        $row = getBebanPostByUrut($req);

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getBebanPostByUrut",
            "data"   => $row
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getFilterBebanPost") {
        $data = getFilterBebanPost();

        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getFilterBebanPost",
            "data"   => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "createBebanPost") {
        $row = createBebanPost($req);

        http_response_code(201);
        echo json_encode([
            "status"  => 201,
            "method"  => "createBebanPost",
            "message" => "Beban post berhasil ditambahkan",
            "data"    => $row
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "exportSiswa") {
        exportSiswa($req);
    }

    if ($method === "importSiswa") {
        $result = importSiswa($req);

        http_response_code(200);
        echo json_encode([
            "status"  => 200,
            "method"  => "importSiswa",
            "message" => "Import selesai",
            "data"    => $result
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "getSettingAtributSiswa") {
        $rows = getSettingAtributSiswa($req);
        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "getSettingAtributSiswa",
            "data" => $rows
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === "importSettingAtributSiswa") {
        $result = importSettingAtributSiswa($req);
        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "method" => "importSettingAtributSiswa",
            "message" => "Import atribut selesai",
            "data" => $result
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(422);
    echo json_encode([
        "status" => 422,
        "message" => "Method tidak valid"
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    writeLog([
        "level" => "ERROR",
        "event" => "EXCEPTION",
        "type" => get_class($e),
        "message" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);

    http_response_code(500);
    $clientMessage = $e->getMessage();
    if ($clientMessage === '' || strlen($clientMessage) > 500) {
        $clientMessage = 'Gagal memproses permintaan (cek log server).';
    }
    echo json_encode([
        "status" => 500,
        "message" => $clientMessage
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
