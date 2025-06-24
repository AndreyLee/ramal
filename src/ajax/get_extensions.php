<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$searchTerm = $_GET['term'] ?? '';

try {
    $sql = "SELECT s.id AS sector_id, s.name AS sector_name,
                   e.id AS extension_id, e.number AS extension_number, e.type AS extension_type,
                   p.name AS person_name
            FROM sectors s
            LEFT JOIN extensions e ON s.id = e.sector_id AND e.status = 'Atribuído'
            LEFT JOIN persons p ON e.person_id = p.id ";

    $params = [];
    if (!empty($searchTerm)) {
        // Ensure search term is treated as a pattern for LIKE clause
        $searchTermWildcard = '%' . $searchTerm . '%';
        // Search in person's name or extension number
        $sql .= " WHERE (p.name LIKE :term OR e.number LIKE :term) AND e.status = 'Atribuído'";
        // We need to ensure that even with search, we only get assigned extensions.
        // If a sector has no matching extensions, it might be omitted by this JOIN logic
        // A more complex query might be needed if sectors without matching extensions should still show.
        // For now, this will filter to show only sectors with matching, assigned extensions.
        $params[':term'] = $searchTermWildcard;
    } else {
        // If no search term, only get assigned extensions
         $sql .= " WHERE e.status = 'Atribuído' OR e.id IS NULL"; // Show sectors even if they have no extensions
    }

    $sql .= " ORDER BY s.name, p.name, e.number";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
        // Only add extension if it exists and is assigned
        if ($row['extension_id'] && $row['person_name']) {
            $sectors[$sector_id]['extensions'][] = [
                'id' => $row['extension_id'],
                'number' => $row['extension_number'],
                'type' => $row['extension_type'],
                'person_name' => $row['person_name']
            ];
        }
    }

    // Filter out sectors that are empty AFTER a search
    if (!empty($searchTerm)) {
        $filteredSectors = [];
        foreach ($sectors as $sector) {
            if (!empty($sector['extensions'])) {
                $filteredSectors[] = $sector;
            }
        }
        echo json_encode(array_values($filteredSectors));
    } else {
        echo json_encode(array_values($sectors));
    }

} catch (PDOException $e) {
    error_log("AJAX Error fetching extensions: " . $e->getMessage());
    echo json_encode(['error' => 'Could not fetch extensions. ' . $e->getMessage()]);
}
?>
