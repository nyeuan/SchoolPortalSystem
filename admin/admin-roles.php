<?php
// File: admin/admin-roles.php
$required_role = 'Admin';
include '../includes/session_check.php';
include '../config/db.php'; 

$first_name = htmlspecialchars($_SESSION['first_name'] ?? 'Admin');
$last_name  = htmlspecialchars($_SESSION['last_name'] ?? 'User');
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

$where_clause = "";
$params = [];
if ($search !== '') {
    $where_clause = "WHERE u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search OR r.RoleName LIKE :search";
    $params[':search'] = "%$search%";
}

try {
    $countQuery = "SELECT COUNT(*) FROM Users u INNER JOIN Role r ON u.FK_Role_ID = r.Role_ID $where_clause";
    $count_stmt = $pdo->prepare($countQuery);
    $count_stmt->execute($params);
    $totalRows = $count_stmt->fetchColumn();
    $totalPages = max(1, ceil($totalRows / $limit));

    $sql = "
        SELECT u.User_ID, u.FirstName, u.LastName, u.Email, r.RoleName
        FROM Users u
        INNER JOIN Role r ON u.FK_Role_ID = r.Role_ID
        $where_clause
        ORDER BY u.LastName, u.FirstName
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Roles</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { school: { green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430' } } } }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-64 h-screen overflow-y-auto p-8">
        <section class="bg-white rounded-2xl p-6 shadow-lg mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-school-green">User Roles & Accounts</h1>
                <a href="create-users.php" class="bg-school-green text-white px-5 py-3 rounded-xl font-semibold">+ Create Account</a>
            </div>
            <form method="GET" class="mb-4 mt-4">
                <input type="text" name="search" placeholder="Search name, email, or role..." value="<?= htmlspecialchars($search) ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
            </form>
        </section>

        <section class="bg-white rounded-2xl p-6 shadow-lg">
            <table class="w-full">
                <thead>
                    <tr class="border-b"><th class="text-left py-3">Name</th><th class="text-left">Email</th><th class="text-left">Role</th><th class="text-left">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="4" class="text-center py-8 text-gray-500">No users found.</td></tr>
                    <?php else: foreach($users as $row): ?>
                        <tr class="border-b">
                            <td class="py-4"><?= htmlspecialchars($row['FirstName'] . " " . $row['LastName']) ?></td>
                            <td><?= htmlspecialchars($row['Email']) ?></td>
                            <td><?= htmlspecialchars($row['RoleName']) ?></td>
                            <td>
                                <a href="edit-users.php?id=<?= $row['User_ID'] ?>" class="text-blue-600 mr-3 font-semibold">Edit</a>
                                <button type="button" onclick="openDeleteModal(<?= $row['User_ID'] ?>, '<?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?>')" class="text-red-600 font-semibold">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <div class="flex justify-center items-center gap-2 mt-6 flex-wrap">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">← Prev</a>
                <?php endif; foreach (range(max(1, $page - 2), min($totalPages, $page + 2)) as $i): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 rounded-lg <?= ($page == $i) ? 'bg-school-green text-white' : 'bg-gray-200 hover:bg-gray-300' ?>"><?= $i ?></a>
                <?php endforeach; if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">Next →</a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <div id="deleteModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-2xl w-full max-w-md">
            <h2 class="text-2xl font-bold text-red-600 mb-4">Delete User</h2>
            <p id="deleteMessage" class="text-gray-700 mb-6"></p>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded-lg bg-gray-300">Cancel</button>
                <a id="confirmDeleteBtn" href="#" class="px-4 py-2 rounded-lg bg-red-600 text-white font-bold">Delete</a>
            </div>
        </div>
    </div>
    <script>
        function openDeleteModal(userId, userName){
            document.getElementById("deleteModal").classList.remove("hidden");
            document.getElementById("deleteMessage").innerHTML = "Are you sure you want to delete <strong>" + userName + "</strong>?";
            document.getElementById("confirmDeleteBtn").href = "delete-users.php?id=" + userId;
        }
        function closeDeleteModal(){ document.getElementById("deleteModal").classList.add("hidden"); }
    </script>
</body>
</html>