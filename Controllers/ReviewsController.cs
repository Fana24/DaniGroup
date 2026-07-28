using DaniGroup.Data;
using DaniGroup.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;

namespace DaniGroup.Controllers
{
    [Authorize]
    public class ReviewsController : Controller
    {
        private readonly ApplicationDbContext _context;
        private readonly UserManager<IdentityUser> _userManager;

        public ReviewsController(ApplicationDbContext context, UserManager<IdentityUser> userManager)
        {
            _context = context;
            _userManager = userManager;
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Add(ProductReview model)
        {
            var user = await _userManager.GetUserAsync(User);
            if (user == null) return Challenge();

            if (!ModelState.IsValid)
            {
                return RedirectToAction("Details", "Products", new { id = model.ProductId });
            }

            model.UserId = user.Id;
            model.ReviewerName = user.Email ?? "Customer";

            _context.ProductReviews.Add(model);
            await _context.SaveChangesAsync();

            return RedirectToAction("Details", "Products", new { id = model.ProductId });
        }
    }
}