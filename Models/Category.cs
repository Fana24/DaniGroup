using System.ComponentModel.DataAnnotations;

namespace DaniGroup.Models
{
    public class Category
    {
        public int Id { get; set; }

        [Required]
        [StringLength(100)]
        public string Name { get; set; }

        public List<Product> Products { get; set; } = new List<Product>();
    }
}