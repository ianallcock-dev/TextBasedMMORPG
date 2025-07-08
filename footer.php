  <!-- Footer -->
  <footer>
    <div class="container">
      &copy; <?= date('Y') ?> Steam & Steel MMO
    </div>
  </footer>
<script>
  document.querySelector('.nav-toggle')
    .addEventListener('click', () => {
      document.querySelector('nav').classList.toggle('show');
    });
</script>

</body>
</html>