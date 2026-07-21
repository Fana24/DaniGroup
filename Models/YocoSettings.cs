namespace DaniGroup.Models
{
    public class YocoSettings
    {
        public string PublicKey { get; set; } = "";
        public string SecretKey { get; set; } = "";
        public string ApiBaseUrl { get; set; } = "";
        public string SuccessUrl { get; set; } = "";
        public string CancelUrl { get; set; } = "";
        public string WebhookSecret { get; set; } = "";
    }
}