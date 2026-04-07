-- Data Penerimaan: WHERE FSTSBolehBayar + PAIDST (lunas) + PAIDDT IS NOT NULL, ORDER BY AA DESC.
-- PRIMARY KEY (AA, CUSTID, BILLCD) sudah mengurutkan fisik per AA, tapi filter lunas tidak ada di awal PK.
-- IDX_BILL dimulai dari CUSTID → tidak dipakai optimiser untuk "semua tagihan lunas urut AA".
-- Jalankan di DB yang sama dengan aplikasi keuangan:

CREATE INDEX idx_scctbill_penerimaan_aa
ON scctbill (FSTSBolehBayar, PAIDST, AA DESC);

-- Setelah dibuat, cek dengan:
-- EXPLAIN SELECT AA FROM scctbill
-- WHERE FSTSBolehBayar = 1 AND PAIDST = '1' AND PAIDDT IS NOT NULL
-- ORDER BY AA DESC LIMIT 10;
