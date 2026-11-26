-- 03_function.sql ⤳ 2 function bantu: fn_hitung_subtotal (harga x jumlah) dan fn_calculate_ppn (PPN 11%).
DROP FUNCTION IF EXISTS `fn_hitung_subtotal`;
-- hitung total harga = harga satuan dikali x jumlah barang
DELIMITER ;;
CREATE FUNCTION `fn_hitung_subtotal`(
   -- ini itu parameter = nilai buat lanjutin proses
   p_harga INT,
    p_jumlah INT
    -- ngeharusin biar hasilnya bulat
) RETURNS int
-- hasilin nilai yang sama
    DETERMINISTIC
BEGIN
-- hutang harga . jumlah
    RETURN p_harga * p_jumlah;
END ;;
DELIMITER ;

DROP FUNCTION IF EXISTS `fn_calculate_ppn`;
-- hitung PPN = subtotal x 0.11

DELIMITER ;;
CREATE FUNCTION `fn_calculate_ppn`(
    p_subtotal DECIMAL(14,2)
) RETURNS DECIMAL(14,2)
    DETERMINISTIC
BEGIN
    RETURN ROUND(p_subtotal * 0.11);
END ;;
DELIMITER ;
-- Mengembalikan tanda akhir query menjadi ;.
