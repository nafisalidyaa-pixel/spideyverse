DROP FUNCTION IF EXISTS `fn_hitung_subtotal`;

DELIMITER ;;
CREATE FUNCTION `fn_hitung_subtotal`(
    p_harga INT,
    p_jumlah INT
) RETURNS int
    DETERMINISTIC
BEGIN
    RETURN p_harga * p_jumlah;
END ;;
DELIMITER ;
