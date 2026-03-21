# SuGanta API Documentation

Welcome to the SuGanta API documentation! This folder contains comprehensive documentation for all API features and services.

## 📚 Table of Contents

### General Documentation
- [API Documentation](./API_DOCUMENTATION.md) - General API overview and guidelines
- [Optimization Summary](./OPTIMIZATION_SUMMARY.md) - Overall project optimization summary

### Support Ticket System
- [Support Ticket API](./SUPPORT_TICKET_API.md) - **Complete API reference** for support tickets
- [Support Ticket Quick Reference](./SUPPORT_TICKET_QUICK_REFERENCE.md) - **Quick start guide** for developers
- [Support Ticket Optimization Summary](./SUPPORT_TICKET_OPTIMIZATION_SUMMARY.md) - Detailed optimization changes
- [Before & After Comparison](./BEFORE_AFTER_COMPARISON.md) - Visual comparison of improvements
- [Setup Storage](./SETUP_STORAGE.md) - Storage configuration and troubleshooting
- [Implementation Checklist](./IMPLEMENTATION_CHECKLIST.md) - Deployment and testing checklist
- [Support Ticket API (Legacy)](./SupportTicketApi.md) - Legacy documentation

### Notification System
- [Notification API](./NotificationApi.md) - List user notifications with pagination
- [Notification Architecture](./NOTIFICATION_ARCHITECTURE.md) - Resource-based notification system design

### Feature-Specific APIs
- [Dashboard API](./DashboardApi.md) - User and admin dashboard endpoints
- [Form Auto-Fill API](./FormAutofillApi.md) - Profile data for pre-populating forms (contact, lead, profile edit)
- [Notification API](./NotificationApi.md) - User notifications with pagination
- [Chat API V3 (Flutter + Realtime)](./ChatApiV3Flutter.md) - Complete chat APIs with Reverb realtime integration
- [Payment API](./PaymentApi.md) - Payment history and invoice endpoints
- [Profile API](./ProfileApi.md) - User profile management endpoints
- [Registration API](./RegistrationApi.md) - User registration endpoints
- [Options API](./OptionApi.md) - Dropdown options and configuration
- [Portfolio API](./PortfolioApi.md) - Portfolio management (create, update, files, images)
- [Study Requirement API](./StudyRequirementApi.md) - Create, list, view study requirements; connect to requirements

### Integrations
- [SMS Integration](./SMS_INTEGRATION.md) - SMS service integration guide

---

## 🚀 Quick Start

### For New Developers

1. **Start with**: [API Documentation](./API_DOCUMENTATION.md) for general overview
2. **Then read**: [Support Ticket Quick Reference](./SUPPORT_TICKET_QUICK_REFERENCE.md) for hands-on examples
3. **Setup**: Follow [Setup Storage](./SETUP_STORAGE.md) for file upload configuration

### For Frontend Developers

**Essential Reading:**
- [Support Ticket API](./SUPPORT_TICKET_API.md) - Complete endpoint reference with examples
- [Notification Architecture](./NOTIFICATION_ARCHITECTURE.md) - How to handle notifications

**Client Examples:**
- React/JavaScript examples in [Support Ticket API](./SUPPORT_TICKET_API.md#usage-examples)
- Flutter/Dart examples in [Support Ticket API](./SUPPORT_TICKET_API.md#usage-examples)

### For Backend Developers

**Essential Reading:**
- [Support Ticket Optimization Summary](./SUPPORT_TICKET_OPTIMIZATION_SUMMARY.md) - Code architecture
- [Before & After Comparison](./BEFORE_AFTER_COMPARISON.md) - Implementation patterns
- [Implementation Checklist](./IMPLEMENTATION_CHECKLIST.md) - Deployment guide

### For DevOps/System Admins

**Essential Reading:**
- [Setup Storage](./SETUP_STORAGE.md) - Server configuration
- [Implementation Checklist](./IMPLEMENTATION_CHECKLIST.md) - Production deployment
- [Support Ticket API](./SUPPORT_TICKET_API.md#troubleshooting) - Troubleshooting guide

---

## 📖 Documentation Guide

### Support Ticket System (Complete Feature)

The support ticket system is fully documented with multiple guides for different use cases:

#### 1. **For Quick Reference** → [Support Ticket Quick Reference](./SUPPORT_TICKET_QUICK_REFERENCE.md)
- Quick start guide
- Common operations
- Code snippets
- Error handling

#### 2. **For Complete API Details** → [Support Ticket API](./SUPPORT_TICKET_API.md)
- All endpoints documented
- Request/response examples
- Validation rules
- Client integration examples
- Troubleshooting guide

#### 3. **For Setup & Configuration** → [Setup Storage](./SETUP_STORAGE.md)
- Installation steps
- Platform-specific commands
- Permission configuration
- Troubleshooting
- Maintenance scripts

#### 4. **For Understanding Changes** → [Support Ticket Optimization Summary](./SUPPORT_TICKET_OPTIMIZATION_SUMMARY.md)
- What was changed
- Why it was changed
- Code comparisons
- Benefits achieved

#### 5. **For Visual Comparison** → [Before & After Comparison](./BEFORE_AFTER_COMPARISON.md)
- Side-by-side code comparison
- Feature comparison table
- Security improvements
- Performance metrics

#### 6. **For Deployment** → [Implementation Checklist](./IMPLEMENTATION_CHECKLIST.md)
- Pre-deployment checklist
- Testing checklist
- Security hardening
- Monitoring setup

---

## 🎯 Common Tasks

### Create a Support Ticket with Attachment
```bash
curl -X POST http://localhost:8000/api/v1/support-tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "subject=Issue Title" \
  -F "message=Description" \
  -F "priority=high" \
  -F "category=technical" \
  -F "attachment=@file.png"
```

**See**: [Support Ticket Quick Reference](./SUPPORT_TICKET_QUICK_REFERENCE.md#create-ticket)

### Setup File Storage
```bash
# Create symlink
php artisan storage:link

# Create directory
mkdir storage/app/public/support-tickets
```

**See**: [Setup Storage](./SETUP_STORAGE.md#quick-setup-run-these-commands)

### Handle Notifications
```javascript
// Client-side notification handling
if (notification.resource_type === 'support_ticket') {
  navigate(`/tickets/${notification.resource_id}`);
}
```

**See**: [Notification Architecture](./NOTIFICATION_ARCHITECTURE.md#client-implementation-examples)

---

## 🔍 Find What You Need

### By Topic

| Topic | Documentation |
|-------|---------------|
| **API Endpoints** | [Dashboard API](./DashboardApi.md), [Notification API](./NotificationApi.md), [Payment API](./PaymentApi.md), [Portfolio API](./PortfolioApi.md), [Profile API](./ProfileApi.md), [Support Ticket API](./SUPPORT_TICKET_API.md), [Registration API](./RegistrationApi.md), [Options API](./OptionApi.md), [Study Requirement API](./StudyRequirementApi.md) |
| **File Uploads** | [Support Ticket API](./SUPPORT_TICKET_API.md#file-upload-specifications), [Setup Storage](./SETUP_STORAGE.md) |
| **Notifications** | [Notification API](./NotificationApi.md), [Notification Architecture](./NOTIFICATION_ARCHITECTURE.md) |
| **Payments** | [Payment API](./PaymentApi.md) |
| **Authentication** | [API Documentation](./API_DOCUMENTATION.md) |
| **SMS Integration** | [SMS Integration](./SMS_INTEGRATION.md) |
| **Deployment** | [Implementation Checklist](./IMPLEMENTATION_CHECKLIST.md) |
| **Troubleshooting** | [Setup Storage](./SETUP_STORAGE.md#troubleshooting), [Support Ticket API](./SUPPORT_TICKET_API.md#troubleshooting) |

### By Role

| Role | Start Here |
|------|------------|
| **Frontend Developer** | [Support Ticket Quick Reference](./SUPPORT_TICKET_QUICK_REFERENCE.md) → [Support Ticket API](./SUPPORT_TICKET_API.md) |
| **Backend Developer** | [Support Ticket Optimization Summary](./SUPPORT_TICKET_OPTIMIZATION_SUMMARY.md) → [Before & After Comparison](./BEFORE_AFTER_COMPARISON.md) |
| **Mobile Developer** | [Support Ticket API](./SUPPORT_TICKET_API.md#usage-examples) → [Notification Architecture](./NOTIFICATION_ARCHITECTURE.md) |
| **DevOps Engineer** | [Setup Storage](./SETUP_STORAGE.md) → [Implementation Checklist](./IMPLEMENTATION_CHECKLIST.md) |
| **Project Manager** | [Optimization Summary](./OPTIMIZATION_SUMMARY.md) → [Before & After Comparison](./BEFORE_AFTER_COMPARISON.md) |

### By Task

| Task | Documentation |
|------|---------------|
| **Integrate file uploads** | [Support Ticket API](./SUPPORT_TICKET_API.md#create-support-ticket) |
| **Setup production server** | [Setup Storage](./SETUP_STORAGE.md#for-production-linuxmac) |
| **Handle notifications** | [Notification Architecture](./NOTIFICATION_ARCHITECTURE.md#client-implementation-examples) |
| **Debug upload issues** | [Setup Storage](./SETUP_STORAGE.md#troubleshooting) |
| **Understand code changes** | [Before & After Comparison](./BEFORE_AFTER_COMPARISON.md) |
| **Deploy to production** | [Implementation Checklist](./IMPLEMENTATION_CHECKLIST.md#deployment-checklist) |

---

## 📊 Feature Status

| Feature | Status | Documentation |
|---------|--------|---------------|
| Dashboard API | ✅ Production Ready | [Dashboard API Docs](./DashboardApi.md) |
| Notification API | ✅ Production Ready | [Notification API Docs](./NotificationApi.md) |
| Payment API | ✅ Production Ready | [Payment API Docs](./PaymentApi.md) |
| Portfolio API | ✅ Production Ready | [Portfolio API Docs](./PortfolioApi.md) |
| Profile API | ✅ Production Ready | [Profile API Docs](./ProfileApi.md) |
| Support Tickets | ✅ Production Ready | [Complete Docs](./SUPPORT_TICKET_API.md) |
| File Uploads | ✅ Production Ready | [Setup Guide](./SETUP_STORAGE.md) |
| Notifications | ✅ Production Ready | [Architecture](./NOTIFICATION_ARCHITECTURE.md) |
| Registration | ✅ Production Ready | [API Docs](./RegistrationApi.md) |
| Study Requirements | ✅ Production Ready | [Study Requirement API](./StudyRequirementApi.md) |
| SMS Integration | ✅ Production Ready | [Integration Guide](./SMS_INTEGRATION.md) |

---

## 🛠️ Development Workflow

### 1. **Read Documentation**
Start with the relevant documentation for your task.

### 2. **Setup Environment**
Follow setup guides for required features (e.g., storage for file uploads).

### 3. **Implement**
Use code examples from documentation as reference.

### 4. **Test**
Follow testing checklists in [Implementation Checklist](./IMPLEMENTATION_CHECKLIST.md).

### 5. **Deploy**
Follow deployment guide in [Implementation Checklist](./IMPLEMENTATION_CHECKLIST.md#deployment-checklist).

---

## 🔐 Security

All features implement:
- ✅ Authentication required (Sanctum)
- ✅ Authorization checks (permission-based)
- ✅ Input validation
- ✅ File type validation (uploads)
- ✅ Rate limiting
- ✅ Audit logging

**See**: [Support Ticket API - Security](./SUPPORT_TICKET_API.md#security-considerations)

---

## 📞 Support

### Common Issues

1. **Storage link not found**
   - **Solution**: Run `php artisan storage:link`
   - **See**: [Setup Storage - Troubleshooting](./SETUP_STORAGE.md#troubleshooting)

2. **File upload fails**
   - **Solution**: Check PHP upload limits and permissions
   - **See**: [Setup Storage - Troubleshooting](./SETUP_STORAGE.md#problem-file-too-large)

3. **Notifications not working**
   - **Solution**: Check notification service integration
   - **See**: [Notification Architecture](./NOTIFICATION_ARCHITECTURE.md)

### Getting Help

1. Check relevant documentation
2. Search for error in troubleshooting sections
3. Review code examples
4. Check logs: `storage/logs/laravel.log`

---

## 📝 Documentation Standards

All documentation in this folder follows these standards:

- ✅ Clear structure with table of contents
- ✅ Code examples for common operations
- ✅ Request/response examples
- ✅ Error handling examples
- ✅ Troubleshooting sections
- ✅ Platform-specific instructions
- ✅ Security considerations
- ✅ Best practices

---

## 🎉 Quick Links

### Most Used Documents
1. [Support Ticket Quick Reference](./SUPPORT_TICKET_QUICK_REFERENCE.md) - For daily development
2. [Support Ticket API](./SUPPORT_TICKET_API.md) - For complete reference
3. [Setup Storage](./SETUP_STORAGE.md) - For configuration
4. [Notification Architecture](./NOTIFICATION_ARCHITECTURE.md) - For client integration

### Getting Started
1. [API Documentation](./API_DOCUMENTATION.md) - Start here for overview
2. [Support Ticket Quick Reference](./SUPPORT_TICKET_QUICK_REFERENCE.md) - Then try this
3. [Setup Storage](./SETUP_STORAGE.md) - Setup your environment

### Advanced Topics
1. [Support Ticket Optimization Summary](./SUPPORT_TICKET_OPTIMIZATION_SUMMARY.md) - Architecture details
2. [Before & After Comparison](./BEFORE_AFTER_COMPARISON.md) - Implementation patterns
3. [Implementation Checklist](./IMPLEMENTATION_CHECKLIST.md) - Production deployment

---

**Happy Coding! 🚀**

*Last Updated: March 6, 2026*
