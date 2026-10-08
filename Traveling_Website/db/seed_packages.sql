USE globetrek_adventures;

-- Clear out any existing packages first (safe to re-run)
DELETE FROM packages;

INSERT INTO packages (destination_id, title, description, price, duration_days, max_travelers) VALUES
(1, 'Colombo City Explorer', 'A full day discovering Colombo''s highlights: the Colombo National Museum, Gangaramaya Temple, Independence Square, and the Nelum Pokuna (Lotus) Tower.', 55.00, 1, 15),
(2, 'Galle Fort & Coast Discovery', 'Explore the historic Dutch Fort, Fort Lighthouse, and the Kanneliya rainforest reserve on the southern coast.', 65.00, 2, 12),
(3, 'Ella Hills Adventure', 'Trek to Mini Adam''s Peak, visit the Nine Arches (Demodara) Bridge, and see the stunning Ravana Ella Falls.', 70.00, 2, 10),
(4, 'Dambulla Heritage & Skies', 'Visit the ancient Dambulla Cave Temple, take in the Rose Quartz Mountain, and finish with a sunrise hot air balloon ride.', 160.00, 2, 8),
(5, 'Hatton Tea Country Escape', 'Trek Adam''s Peak for sunrise, kayak on Castlereagh reservoir, and visit the historic Warleigh Church amid tea plantations.', 85.00, 2, 10);

