<?php
include 'db_connect_pdo.php';

$new_image = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRuGyA_dNp-PylIHWJkacA9rrypRpB7ojx_nw&s';
$title = 'The Science of mRNA';

try {
    $stmt = $pdo->prepare("UPDATE articles SET image = :image WHERE title = :title");
    $stmt->execute(['image' => $new_image, 'title' => $title]);
    
    if ($stmt->rowCount() > 0) {
        echo "Success: Image updated for article '$title'.\n";
    } else {
        echo "No changes made: Article found but image might be same, or article not found.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
