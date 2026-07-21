using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace DaniGroup.Models
{
    public class Order
    {
        public int Id { get; set; }

        [Required]
        public string UserId { get; set; } = "";

        [Required]
        [StringLength(100)]
        public string FullName { get; set; } = "";

        [Required]
        [StringLength(150)]
        public string Email { get; set; } = "";

        [Required]
        [StringLength(200)]
        public string AddressLine1 { get; set; } = "";

        [StringLength(200)]
        public string? AddressLine2 { get; set; }

        [Required]
        [StringLength(100)]
        public string City { get; set; } = "";

        [Required]
        [StringLength(100)]
        public string State { get; set; } = "";

        [Required]
        [StringLength(30)]
        public string PostalCode { get; set; } = "";

        [Required]
        [StringLength(50)]
        public string Country { get; set; } = "";

        [Required]
        [StringLength(30)]
        public string OrderStatus { get; set; } = "Pending";

        public DateTime CreatedAt { get; set; } = DateTime.Now;

        [Column(TypeName = "decimal(18,2)")]
        public decimal TotalAmount { get; set; }

        [StringLength(50)]
        public string PaymentStatus { get; set; } = "Pending";

        [StringLength(100)]
        public string PaymentProvider { get; set; } = "Yoco";

        [StringLength(200)]
        public string? PaymentReference { get; set; }

        [StringLength(200)]
        public string? PaymentCheckoutId { get; set; }

        public List<OrderItem> OrderItems { get; set; } = new();
    }
}