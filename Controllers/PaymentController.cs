using DaniGroup.Data;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace DaniGroup.Controllers
{
    [Authorize]
    public class PaymentController : Controller
    {
        private readonly ApplicationDbContext _context;

        public PaymentController(ApplicationDbContext context)
        {
            _context = context;
        }

        public async Task<IActionResult> Success(int orderId)
        {
            var order = await _context.Orders
                .Include(o => o.OrderItems)
                .ThenInclude(oi => oi.Product)
                .FirstOrDefaultAsync(o => o.Id == orderId);

            if (order == null)
                return NotFound();

            order.PaymentStatus = "Paid";
            order.OrderStatus = "Processing";

            await _context.SaveChangesAsync();

            return View(order);
        }

        public async Task<IActionResult> Cancel(int orderId)
        {
            var order = await _context.Orders.FirstOrDefaultAsync(o => o.Id == orderId);

            if (order == null)
                return NotFound();

            order.PaymentStatus = "Cancelled";
            order.OrderStatus = "Pending";

            await _context.SaveChangesAsync();

            return View(order);
        }
    }
}