</main>
    <footer class="bg-gray-800 text-center text-sm py-4 mt-auto border-t border-gray-700">
        <p class="text-gray-400">&copy; <?php echo date('Y'); ?> 黑色沙漠代练日志. All Rights Reserved.</p>
    </footer>
</body>
</html>
<?php
if(isset($conn)){
    $conn->close();
}
?>