USE globetrek_adventures;

UPDATE packages p
JOIN destinations d ON p.destination_id = d.destination_id
SET p.price = CASE d.name
    WHEN 'Colombo'  THEN 25000.00
    WHEN 'Galle'    THEN 30000.00
    WHEN 'Ella'     THEN 50000.00
    WHEN 'Dambulla' THEN 45000.00
    WHEN 'Hatton'   THEN 35000.00
END;
