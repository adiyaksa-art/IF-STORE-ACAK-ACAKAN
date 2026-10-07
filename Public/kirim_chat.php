// ... kode simpan chat selesai ...

// Misal $penerima_id adalah ID orang yang dichat
$pesan = "Anda menerima pesan baru!";
mysqli_query($conn, "INSERT INTO notifications (user_id, message) VALUES ('$penerima_id', '$pesan')");