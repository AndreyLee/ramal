<?php
require_once __DIR__ . '/../src/includes/db.php'; // Adjusted path

// Function to fetch sectors and their extensions
function getSectorsWithExtensions($pdo) {
    $sql = "SELECT s.id AS sector_id, s.name AS sector_name,
                   e.id AS extension_id, e.number AS extension_number, e.type AS extension_type,
                   p.name AS person_name
            FROM sectors s
            LEFT JOIN extensions e ON s.id = e.sector_id
            LEFT JOIN persons p ON e.person_id = p.id
            WHERE (e.status = 'Atribuído' OR e.status IS NULL) -- Show assigned extensions or sectors without extensions
            ORDER BY s.name, p.name, e.number";
    // If a sector has no extensions, it will still be listed.
    // If an extension is not assigned to a person, person_name will be NULL.

    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sectors = [];
    foreach ($results as $row) {
        $sector_id = $row['sector_id'];
        if (!isset($sectors[$sector_id])) {
            $sectors[$sector_id] = [
                'id' => $sector_id,
                'name' => $row['sector_name'],
                'extensions' => []
            ];
        }
        if ($row['extension_id']) { // Only add extension if it exists
            $sectors[$sector_id]['extensions'][] = [
                'id' => $row['extension_id'],
                'number' => $row['extension_number'],
                'type' => $row['extension_type'],
                'person_name' => $row['person_name'] ?: 'Vago' // Display 'Vago' if no person is assigned
            ];
        }
    }
    return array_values($sectors); // Return as a numerically indexed array
}

$sectorsWithExtensions = [];
try {
    $sectorsWithExtensions = getSectorsWithExtensions($pdo);
} catch (PDOException $e) {
    error_log("Error fetching extensions for public view: " . $e->getMessage());
    // Optionally, set a user-friendly error message to display
    $errorMessage = "Não foi possível carregar os ramais no momento. Tente novamente mais tarde.";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Ramais</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Lista de Ramais</h1>
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Buscar por nome ou ramal...">
                <button id="clearSearchBtn">Limpar</button>
            </div>
        </header>

        <main id="extensionList">
            <?php if (!empty($errorMessage)): ?>
                <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php elseif (empty($sectorsWithExtensions)): ?>
                <p>Nenhum ramal encontrado.</p>
            <?php else: ?>
                <?php foreach ($sectorsWithExtensions as $sector): ?>
                    <div class="sector-group">
                        <h2 class="sector-name"><?php echo htmlspecialchars($sector['name']); ?> (<?php echo count($sector['extensions']); ?>)</h2>
                        <div class="extensions">
                            <?php if (empty($sector['extensions'])): ?>
                                <p class="no-extensions">Nenhum ramal neste setor.</p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($sector['extensions'] as $ext): ?>
                                        <li data-name="<?php echo htmlspecialchars(strtolower($ext['person_name'])); ?>" data-number="<?php echo htmlspecialchars($ext['number']); ?>">
                                            <strong><?php echo htmlspecialchars($ext['number']); ?></strong> -
                                            <?php echo htmlspecialchars($ext['person_name']); ?>
                                            (<?php echo htmlspecialchars($ext['type']); ?>)
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; <?php echo date("Y"); ?> Sistema de Lista de Ramais</p>
            <p><a href="admin/">Área Administrativa</a></p>
        </footer>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
