import 'package:flutter/material.dart';

class ToastHelper {
  static void showSuccess(BuildContext context, String message) {
    _showToast(context, message, const Color(0xFF4CAF50), Icons.check_circle);
  }

  static void showError(BuildContext context, String message) {
    _showToast(context, message, const Color(0xFFE53935), Icons.error);
  }

  static void showInfo(BuildContext context, String message) {
    _showToast(context, message, const Color(0xFF2196F3), Icons.info);
  }

  static void showWarning(BuildContext context, String message) {
    _showToast(context, message, const Color(0xFFFFA000), Icons.warning);
  }

  static void _showToast(BuildContext context, String message,
      Color backgroundColor, IconData iconData) {
    final snackBar = SnackBar(
      content: Row(
        children: [
          Icon(
            iconData,
            color: Colors.white,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(color: Colors.white),
            ),
          ),
        ],
      ),
      backgroundColor: backgroundColor,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(8),
      ),
      margin: const EdgeInsets.all(12),
      duration: const Duration(seconds: 3),
    );

    ScaffoldMessenger.of(context).showSnackBar(snackBar);
  }
}
