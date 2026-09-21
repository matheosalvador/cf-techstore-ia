INSERT INTO categories (id, name) VALUES
(1, 'Ordinateurs'),
(2, 'Composants'),
(3, 'Périphériques');

INSERT INTO users (id, email, password_hash, first_name, last_name, role) VALUES
(1, 'alice.martin@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice', 'Martin', 'customer'),
(2, 'bob.dupont@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob', 'Dupont', 'customer'),
(3, 'sophie.bernard@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sophie', 'Bernard', 'manager');

INSERT INTO products (id, category_id, name, description, price_cents, available) VALUES
(1, 1, 'Laptop Nova 14', 'Ordinateur portable compact avec processeur basse consommation, 16 Go de mémoire et écran mat adapté au télétravail.', 89900, 1),
(2, 1, 'Workstation Atlas Pro', 'Station de travail puissante pour montage vidéo, virtualisation et calcul applicatif intensif.', 249900, 1),
(3, 1, 'Mini PC Helio', 'Mini ordinateur silencieux pour bureautique, affichage dynamique ou poste d accueil léger.', 42900, 1),
(4, 1, 'Laptop Student 13', 'Portable léger avec autonomie confortable pour prise de notes, navigation et visioconférence.', 59900, 0),
(5, 2, 'SSD NVMe 1 To RapidCore', 'Stockage NVMe rapide pour accélérer le démarrage système, les outils de développement et les jeux.', 10990, 1),
(6, 2, 'Carte graphique Vector 4060', 'Carte graphique milieu de gamme adaptée au jeu en 1080p, à la création légère et au calcul GPU occasionnel.', 34900, 1),
(7, 2, 'Mémoire DDR5 32 Go', 'Kit mémoire double canal pour postes récents, utile pour multitâche, IDE lourds et machines virtuelles.', 13900, 1),
(8, 2, 'Alimentation 650W Bronze', 'Bloc alimentation fiable pour configuration bureautique avancée ou PC joueur raisonnable.', 7990, 0),
(9, 3, 'Clavier mécanique Orion', 'Clavier mécanique rétroéclairé avec interrupteurs tactiles pour saisie intensive et jeux.', 8990, 1),
(10, 3, 'Souris ErgoTrack', 'Souris ergonomique sans fil avec capteur précis et autonomie longue durée.', 4990, 1),
(11, 3, 'Écran 27 pouces QHD', 'Moniteur QHD avec dalle IPS, pied réglable et connectique complète pour poste de travail confortable.', 27900, 1),
(12, 3, 'Station USB-C Essential', 'Station d accueil USB-C avec HDMI, Ethernet, alimentation passthrough et ports USB pour ordinateur portable.', 10000, 1),
(13, 3, 'Casque CallCenter Lite', 'Casque filaire léger avec micro antibruit pour appels quotidiens et support client.', 2990, 1);

INSERT INTO orders (id, reference, user_id, status, discount_percent, discounted_subtotal_cents, tax_cents, total_cents, created_at) VALUES
(1, 'ORD-2025-000041', 1, 'validated', 0, 138890, 27778, 166668, '2025-03-12T10:15:00Z'),
(2, 'ORD-2025-000072', 2, 'validated', 10, 125910, 25182, 151092, '2025-07-29T14:35:00Z'),
(3, 'ORD-2026-000018', 1, 'validated', 20, 30392, 6078, 36470, '2026-01-18T09:05:00Z'),
(4, 'ORD-2026-000054', 2, 'validated', 0, 97890, 19578, 117468, '2026-06-04T16:22:00Z'),
(5, 'ORD-2026-000071', 1, 'draft', 0, 10000, 2000, 12000, '2026-08-21T08:00:00Z');

INSERT INTO order_items (order_id, product_id, product_name, unit_price_cents, quantity, line_total_cents) VALUES
(1, 1, 'Laptop Nova 14', 89900, 1, 89900),
(1, 11, 'Écran 27 pouces QHD', 27900, 1, 27900),
(1, 7, 'Mémoire DDR5 32 Go', 13900, 1, 13900),
(1, 9, 'Clavier mécanique Orion', 8990, 1, 8990),
(2, 6, 'Carte graphique Vector 4060', 34900, 2, 69800),
(2, 5, 'SSD NVMe 1 To RapidCore', 10990, 2, 21980),
(2, 7, 'Mémoire DDR5 32 Go', 13900, 2, 27800),
(2, 10, 'Souris ErgoTrack', 4990, 1, 4990),
(3, 12, 'Station USB-C Essential', 10000, 2, 20000),
(3, 10, 'Souris ErgoTrack', 4990, 1, 4990),
(3, 13, 'Casque CallCenter Lite', 2990, 1, 2990),
(3, 9, 'Clavier mécanique Orion', 8990, 1, 8990),
(4, 3, 'Mini PC Helio', 42900, 1, 42900),
(4, 11, 'Écran 27 pouces QHD', 27900, 1, 27900),
(4, 12, 'Station USB-C Essential', 10000, 1, 10000),
(4, 9, 'Clavier mécanique Orion', 8990, 1, 8990),
(4, 10, 'Souris ErgoTrack', 4990, 1, 4990),
(5, 12, 'Station USB-C Essential', 10000, 1, 10000);
