using DaniGroup.Data;
using DaniGroup.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace DaniGroup.Controllers
{
    [Authorize]
    public class ReturnsController : Controller
    {
        private readonly ApplicationDbContext _context;
        private readonly UserManager<IdentityUser> _userManager;
        private readonly IWebHostEnvironment _environment;

        public ReturnsController(
            ApplicationDbContext context,
            UserManager<IdentityUser> userManager,
            IWebHostEnvironment environment)
        {
            _context = context;
            _userManager = userManager;
            _environment = environment;
        }

        [HttpGet]
        public IActionResult Create()
        {
            return View();
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Create(ReturnRequest model, IFormFile? damageImage)
        {
            var user = await _userManager.GetUserAsync(User);

            if (user == null)
                return Challenge();

            if (!ModelState.IsValid)
                return View(model);

            model.UserId = user.Id;
            model.CreatedAt = DateTime.Now;
            model.Status = "Pending";

            if (damageImage != null && damageImage.Length > 0)
            {
                string uploadsFolder = Path.Combine(_environment.WebRootPath, "uploads", "returns");
                Directory.CreateDirectory(uploadsFolder);

                string fileName = Guid.NewGuid().ToString() + Path.GetExtension(damageImage.FileName);
                string filePath = Path.Combine(uploadsFolder, fileName);

                using (var stream = new FileStream(filePath, FileMode.Create))
                {
                    await damageImage.CopyToAsync(stream);
                }

                model.DamageImagePath = "/uploads/returns/" + fileName;
            }

            _context.ReturnRequests.Add(model);
            await _context.SaveChangesAsync();

            return RedirectToAction("MyReturns");
        }

        public async Task<IActionResult> MyReturns()
        {
            var user = await _userManager.GetUserAsync(User);

            if (user == null)
                return Challenge();

            var myReturns = await _context.ReturnRequests
                .Where(r => r.UserId == user.Id)
                .OrderByDescending(r => r.CreatedAt)
                .ToListAsync();

            return View(myReturns);
        }
    }
}