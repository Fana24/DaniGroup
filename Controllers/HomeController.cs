using DaniGroup.Data;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace DaniGroup.Controllers
{
    public class HomeController : Controller
    {
        private readonly ApplicationDbContext _context;

        public HomeController(ApplicationDbContext context)
        {
            _context = context;
        }

        public async Task<IActionResult> Index()
        {
            var featuredProducts = await _context.Products
                .Include(p => p.Category)
                .Where(p => p.IsFeatured)
                .Take(6)
                .ToListAsync();

            return View(featuredProducts);
        }

        public IActionResult About()
        {
            return View();
        }
    }
}