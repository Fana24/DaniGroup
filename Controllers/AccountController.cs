using DaniGroup.Data;
using DaniGroup.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace DaniGroup.Controllers
{
    [Authorize]
    public class AccountController : Controller
    {
        private readonly ApplicationDbContext _context;
        private readonly UserManager<IdentityUser> _userManager;

        public AccountController(ApplicationDbContext context, UserManager<IdentityUser> userManager)
        {
            _context = context;
            _userManager = userManager;
        }

        public async Task<IActionResult> Dashboard()
        {
            var user = await _userManager.GetUserAsync(User);

            var model = new AccountDashboardViewModel
            {
                Email = user.Email,
                CartItemCount = await _context.CartItems.CountAsync(c => c.UserId == user.Id),
                OrderCount = await _context.Orders.CountAsync(o => o.UserId == user.Id),
                ReturnCount = await _context.ReturnRequests.CountAsync(r => r.UserId == user.Id),
                RecentOrders = await _context.Orders
                    .Where(o => o.UserId == user.Id)
                    .OrderByDescending(o => o.CreatedAt)
                    .Take(5)
                    .ToListAsync()
            };

            return View(model);
        }
    }
}