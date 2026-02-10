<?php
include 'db_connect.php';

// Force create Articles Table
$sql = "CREATE TABLE IF NOT EXISTS articles (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    summary TEXT,
    content TEXT NOT NULL,
    image VARCHAR(255),
    author VARCHAR(100) DEFAULT 'MediCore Team',
    published_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'articles' created successfully.<br>";
} else {
    die("Error creating table: " . $conn->error);
}

// Check if empty before seeding
$check = $conn->query("SELECT count(*) as count FROM articles");
$row = $check->fetch_assoc();

if ($row['count'] == 0) {
    echo "Seeding articles...<br>";
    
    $art_img1 = "https://images.unsplash.com/photo-1576091160550-2173dba999ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80";
    $art_img2 = "https://images.unsplash.com/photo-1584362917165-526a968f4b23?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80";
    $art_img3 = "https://images.unsplash.com/photo-1624727828489-a1e03b79bba8?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80";

    $content1 = "Booster doses are an important part of protecting yourself and your community from COVID-19. As immunity wanes over time, a booster shot helps to bring your protection back up to a high level. Recent studies have shown that boosters significantly reduce the risk of severe illness and hospitalization.";
    $content2 = "mRNA vaccines represent a breakthrough in vaccine technology. Unlike traditional vaccines that use weakened or inactivated germs, mRNA vaccines use a molecule called messenger RNA to teach our cells how to make a protein that triggers an immune response. This technology allows for faster development and production of vaccines.";
    $content3 = "The global vaccination effort has reached a new milestone, with over 70% of the world population having received at least one dose. However, disparities remain between improved and developing nations. MediCore is committed to bridging this gap by ensuring equitable distribution of vaccines to all corners of the globe.";

    $stmt = $conn->prepare("INSERT INTO articles (title, summary, content, image, author) VALUES (?, ?, ?, ?, ?)");
    
    // Article 1
    $t1 = 'Why Booster Doses Matter';
    $s1 = 'Understanding the importance of maintaining high immunity levels.';
    $a1 = 'Dr. Sarah Johnson';
    $stmt->bind_param("sssss", $t1, $s1, $content1, $art_img1, $a1);
    $stmt->execute();

    // Article 2
    $t2 = 'The Science of mRNA';
    $s2 = 'How messenger RNA technology is revolutionizing modern medicine.';
    $a2 = 'Research Team';
    $stmt->bind_param("sssss", $t2, $s2, $content2, $art_img2, $a2);
    $stmt->execute();

    // Article 3
    $t3 = 'Global Vaccination Milestone';
    $s3 = 'Over 70% of the global population is now partially vaccinated.';
    $a3 = 'Global Health Watch';
    $stmt->bind_param("sssss", $t3, $s3, $content3, $art_img3, $a3);
    $stmt->execute();

    echo "Articles seeded successfully.<br>";
} else {
    echo "Articles table already has data.<br>";
}

echo "Done.";
?>
