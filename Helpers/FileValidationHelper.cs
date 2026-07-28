namespace DaniGroup.Helpers
{
    public static class FileValidationHelper
    {
        public static readonly string[] AllowedExtensions = { ".jpg", ".jpeg", ".png", ".webp" };
        public const long MaxFileSize = 2 * 1024 * 1024; // 2 MB

        public static bool IsValidImage(IFormFile? file)
        {
            if (file == null || file.Length == 0)
                return false;

            var extension = Path.GetExtension(file.FileName).ToLowerInvariant();

            if (!AllowedExtensions.Contains(extension))
                return false;

            if (file.Length > MaxFileSize)
                return false;

            return true;
        }
    }
}