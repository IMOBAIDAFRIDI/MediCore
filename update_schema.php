<?php
include 'db_connect.php';

// Add image column to vaccines if not exists
$conn->query("ALTER TABLE vaccines ADD COLUMN image VARCHAR(255)");
// Add image column to hospitals if not exists
$conn->query("ALTER TABLE hospitals ADD COLUMN image VARCHAR(255)");

echo "Schema updated successfully. Re-seeding data...<br>";

// Re-seed vaccines with images
$vaccines = [
    [1, 'Covishield', 'Viral Vector Support', 'available', 'https://images.unsplash.com/photo-1618961734760-466979ce35b0?auto=format&fit=crop&w=300&q=80'],
    [2, 'Covaxin', 'Inactivated Virus', 'available', 'https://images.unsplash.com/photo-1584036561566-b93a9499cb6a?auto=format&fit=crop&w=300&q=80'],
    [3, 'Pfizer-BioNTech', 'mRNA Technology', 'available', 'https://images.unsplash.com/photo-1626359556270-dcb1e8c07886?auto=format&fit=crop&w=300&q=80'],
    [4, 'Moderna', 'mRNA Technology', 'available', 'https://images.unsplash.com/photo-1609188075585-6273c5c99c6d?auto=format&fit=crop&w=300&q=80'],
    [5, 'Sputnik V', 'Viral Vector', 'available', 'https://images.unsplash.com/photo-1615461168436-16b7a643ec14?auto=format&fit=crop&w=300&q=80'],
    [6, 'Johnson & Johnson', 'Viral Vector', 'available', 'https://images.unsplash.com/photo-1606905952817-640a256d9539?auto=format&fit=crop&w=300&q=80'],
    [7, 'Sinopharm', 'Inactivated Virus', 'available', 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=300&q=80']
];

foreach ($vaccines as $v) {
    $sql = "INSERT INTO vaccines (id, name, description, availability, image) VALUES ($v[0], '$v[1]', '$v[2]', '$v[3]', '$v[4]') 
            ON DUPLICATE KEY UPDATE image='$v[4]'";
    $conn->query($sql);
}

// Re-seed a few hospitals with images if possible (simplified update)
$hospitals = $conn->query("SELECT id FROM hospitals");
while($h = $hospitals->fetch_assoc()) {
    $img = 'https://images.unsplash.com/photo-1587351021759-3e566b9af923?auto=format&fit=crop&w=300&q=80'; // Generic hospital image
    $conn->query("UPDATE hospitals SET image='$img' WHERE id={$h['id']}");
}

echo "Data seeded.";
?>
