<?php
session_start();
require_once '../db_conn/database.php';
require_once '../includes/functions.php';

// Pagination settings
$items_per_page = 9; // Show 9 products per page (3x3 grid)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Filter settings
$category_name = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$view = isset($_GET['view']) ? $_GET['view'] : 'products';

// Build query
$where_conditions = [];
$params = [];

if ($category_name) {
    $where_conditions[] = "c.name = ?";
    $params[] = $category_name;
}

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$table_name = $view === 'buy-sell' ? 'buy_and_sell' : 'products';
$count_sql = "SELECT COUNT(*) FROM $table_name p LEFT JOIN categories c ON p.category_id = c.id $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_items = $stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

// Get categories for filter
try {
    $categories = $pdo->query("SELECT name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If categories table doesn't exist, set empty array
    $categories = [];
}

// Get items (products or buy_and_sell)
$order_by = match($sort) {
    'price_low' => 'price ASC',
    'price_high' => 'price DESC',
    'name' => 'name ASC',
    default => 'created_at DESC'
};

$sql = "SELECT p.*, c.name as category_name, u.username as seller_name 
        FROM $table_name p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.seller_id = u.id 
        $where_clause 
        ORDER BY $order_by 
        LIMIT $items_per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
.nav-pills .nav-link.active, .nav-pills .show > .nav-link {
    background-color: #198754 !important;
    color: #fff !important;
}
.nav-pills .nav-link {
    color: #198754;
}
</style>

<main class="container ">
    <!-- Navigation Bar -->
    <div class="nav nav-pills mb-4 justify-content-center">
        <a href="edit_product.php" class="nav-link <?= $view === 'products' ? 'active' : '' ?>">
            <i class="ri-store-2-line me-1"></i> Products
        </a>
        <a href="edit_product.php?view=buy-sell" class="nav-link <?= $view === 'buy-sell' ? 'active' : '' ?>">
            <i class="ri-exchange-dollar-line me-1"></i> Buy & Sell
        </a>
    </div>

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Filters</h5>
                    <form action="" method="GET" class="mb-3">
                        <?php if ($view === 'buy-sell'): ?>
                            <input type="hidden" name="view" value="buy-sell">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['name']) ?>" <?= $category_name === $category['name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Apply Filters</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Items Grid -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><?= $view === 'buy-sell' ? 'Buy & Sell Items' : 'Products' ?></h2>
                <span class="text-muted">Showing <?= min($offset + 1, $total_items) ?>-<?= min($offset + $items_per_page, $total_items) ?> of <?= $total_items ?> items</span>
            </div>
            
            <?php if (empty($items)): ?>
                <div class="alert alert-info">No items found matching your criteria.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($items as $item): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <img src="../assets/images/<?= $view === 'buy-sell' ? 'buy_and_sell' : 'product_images' ?>/<?= htmlspecialchars($item['image']) ?>" 
                                    class="card-img-top" 
                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                    onerror="this.src='../assets/images/<?= $view === 'buy-sell' ? 'buy_and_sell' : 'product_images' ?>/default.jpg'">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                                        <p class="card-text text-muted"><?= htmlspecialchars($item['category_name']) ?></p>
                                        <p class="card-text text-success fw-bold"><?= formatPrice($item['price']) ?></p>
                                        <?php if ($view === 'buy-sell'): ?>
                                            <p class="card-text"><small class="text-muted">Seller: <?= htmlspecialchars($item['seller_name']) ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-end align-items-center gap-2 mt-3">
                                        <?php if ($view !== 'buy-sell'): ?>
                                            <button type="button" 
                                                class="btn btn-success btn-sm btn-edit"
                                                data-id="<?= $item['id'] ?>"
                                                data-name="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>"
                                                data-price="<?= $item['price'] ?>"
                                                data-description="<?= htmlspecialchars($item['description'], ENT_QUOTES) ?>"
                                                data-category="<?= htmlspecialchars($item['category_name'], ENT_QUOTES) ?>"
                                                data-view="<?= $view ?>">
                                                Edit
                                            </button>
                                        <?php endif; ?>
                                        <form method="POST" action="delete_product.php" onsubmit="return confirm('Are you sure you want to delete this item?');" style="margin: 0;">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="view" value="<?= $view ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>  
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                        <div class="text-muted">
                            Page <?= $page ?> of <?= $total_pages ?>
                        </div>
                        <div>
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&category=<?= urlencode($category_name) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?><?= $view === 'buy-sell' ? '&view=buy-sell' : '' ?>" 
                                   class="btn btn-outline-success me-2">
                                    <i class="ri-arrow-left-s-line"></i> Previous Page
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>&category=<?= urlencode($category_name) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?><?= $view === 'buy-sell' ? '&view=buy-sell' : '' ?>" 
                                   class="btn btn-success">
                                    Next Page <i class="ri-arrow-right-s-line"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link text-success" href="?page=<?= $page - 1 ?>&category=<?= urlencode($category_name) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?><?= $view === 'buy-sell' ? '&view=buy-sell' : '' ?>">
                                        <i class="ri-arrow-left-s-line"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            // Show first page
                            if ($page > 2): ?>
                                <li class="page-item">
                                    <a class="page-link text-success" href="?page=1&category=<?= urlencode($category_name) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?><?= $view === 'buy-sell' ? '&view=buy-sell' : '' ?>">1</a>
                                </li>
                                <?php if ($page > 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif;
                            endif;

                            // Show pages around current page
                            for ($i = max(1, $page - 1); $i <= min($total_pages, $page + 1); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link <?= $i === $page ? 'bg-success border-success' : 'text-success' ?>" href="?page=<?= $i ?>&category=<?= urlencode($category_name) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?><?= $view === 'buy-sell' ? '&view=buy-sell' : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;

                            // Show last page
                            if ($page < $total_pages - 1): ?>
                                <?php if ($page < $total_pages - 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link text-success" href="?page=<?= $total_pages ?>&category=<?= urlencode($category_name) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?><?= $view === 'buy-sell' ? '&view=buy-sell' : '' ?>"><?= $total_pages ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link text-success" href="?page=<?= $page + 1 ?>&category=<?= urlencode($category_name) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?><?= $view === 'buy-sell' ? '&view=buy-sell' : '' ?>">
                                        Next <i class="ri-arrow-right-s-line"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="edit.php" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Edit Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body row g-3">
        <input type="hidden" name="id" id="edit-id">
        <input type="hidden" name="view" id="edit-view">

        <div class="col-md-6">
          <label for="edit-name" class="form-label">Name</label>
          <input type="text" class="form-control" name="name" id="edit-name" required>
        </div>

        <div class="col-md-6">
          <label for="edit-price" class="form-label">Price</label>
          <input type="number" step="0.01" class="form-control" name="price" id="edit-price" required>
        </div>

        <div class="col-12">
          <label for="edit-description" class="form-label">Description</label>
          <textarea class="form-control" name="description" id="edit-description" rows="4"></textarea>
        </div>

        <div class="col-md-6">
          <label for="edit-category" class="form-label">Category</label>
          <select class="form-select" name="category_id" id="edit-category" required>
            <?php foreach ($categories as $category): ?>
              <option value="<?= htmlspecialchars($category['name']) ?>">
                <?= htmlspecialchars($category['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label for="edit-image" class="form-label">Change Image</label>
          <input type="file" class="form-control" name="image" id="edit-image">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.btn-edit').forEach(button => {
    button.addEventListener('click', function() {
      const id = this.dataset.id;
      const name = this.dataset.name;
      const price = this.dataset.price;
      const description = this.dataset.description;
      const category = this.dataset.category;
      const view = this.dataset.view;

      document.getElementById('edit-id').value = id;
      document.getElementById('edit-name').value = name;
      document.getElementById('edit-price').value = price;
      document.getElementById('edit-description').value = description;
      document.getElementById('edit-category').value = category;
      document.getElementById('edit-view').value = view;

      // Initialize modal properly
      const editModal = new bootstrap.Modal(document.getElementById('editModal'));
      editModal.show();
    });
  });
});
</script>



<?php include 'includes/footer.php'; ?>