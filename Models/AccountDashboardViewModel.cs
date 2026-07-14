namespace DaniGroup.Models
{
    public class AccountDashboardViewModel
    {
        public string Email { get; set; }
        public int CartItemCount { get; set; }
        public int OrderCount { get; set; }
        public int ReturnCount { get; set; }
        public List<Order> RecentOrders { get; set; } = new List<Order>();
    }
}