<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">
      <img src="./assets/img/pokeball.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
    </a>
    <a class="navbar-brand" href="index.php">
      <span class="navbar-brand">Pokédex</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse gap-2 flex-grow-0" id="navbarSupportedContent">
      <div class="d-flex justify-content-between align-items-center gap-2 mt-3 mt-sm-0">
        <?php
        if ($_SESSION['loggedin']) {
          echo '
          <span class="navbar-text">' . $_SESSION['username'] . '</span>
          <a class="btn btn-sm btn-danger d-flex" href="./logout.php">
            <i class="material-symbols-outlined">logout</i>
            <span class="ms-2 d-inline d-sm-none">Logout</span>
          </a>
        ';
        } else {
          echo '
          <a class="btn btn-sm btn-light d-flex" href="./login.php">
            <i class="material-symbols-outlined">login</i>
            <span class="ms-2 d-inline d-sm-none">Login</span>
          </a>
        ';
        }
        ?>
      </div>
    </div>
  </div>
</nav>