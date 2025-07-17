</main>
    <footer class="bg-gray-800 text-center text-sm py-4 mt-8">
        <p>&copy; <?php echo date('Y'); ?> 黑色沙漠代练日志. All Rights Reserved.</p>
    </footer>
</body>
</html>
<?php
if(isset($conn)){
    $conn->close();
}
?>