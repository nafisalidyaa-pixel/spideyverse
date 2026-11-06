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
