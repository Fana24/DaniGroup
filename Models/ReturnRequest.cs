using System.ComponentModel.DataAnnotations;

namespace DaniGroup.Models
{
    public class ReturnRequest
    {
        public int Id { get; set; }

        [Required]
        public string UserId { get; set; }

        [Required]
        [StringLength(150)]
        public string OrderReference { get; set; }

        [Required]
        [StringLength(500)]
        public string Reason { get; set; }

        public string? DamageImagePath { get; set; }

        public DateTime CreatedAt { get; set; } = DateTime.Now;

        public string Status { get; set; } = "Pending";
    }
}