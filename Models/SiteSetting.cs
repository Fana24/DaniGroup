using System.ComponentModel.DataAnnotations;

namespace DaniGroup.Models
{
    public class SiteSetting
    {
        public int Id { get; set; }

        [Required]
        [StringLength(100)]
        public string Key { get; set; } = "";

        [Required]
        [StringLength(500)]
        public string Value { get; set; } = "";
    }
}